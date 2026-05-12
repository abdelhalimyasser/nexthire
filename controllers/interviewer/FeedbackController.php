<?php declare(strict_types=1);
class FeedbackController extends BaseController {

    public function index(): void {
        $this->requireRole(["interviewer","hr_admin","shadow"]);
        $db   = Database::getInstance();
        $uid  = (int)$this->currentUser["id"];
        $role = $this->currentUser["role"];

        // HR Admin sees all panels; others see their assigned panels
        if ($role === "hr_admin") {
            $stmt = $db->prepare("
                SELECT ip.id as panel_id, ip.scheduled_at, jr.title as job_title, u.name as candidate_name,
                       COALESCE(fs.id, 0) as already_submitted
                FROM interview_panels ip
                JOIN job_requisitions jr ON ip.job_id = jr.id
                JOIN applications a ON ip.application_id = a.id
                JOIN users u ON a.candidate_id = u.id
                LEFT JOIN feedback_submissions fs ON fs.panel_id=ip.id AND fs.interviewer_id=:uid
                WHERE ip.status IN ('active','completed')
                ORDER BY ip.scheduled_at DESC
            ");
            $stmt->execute(["uid" => $uid]);
        } else {
            $stmt = $db->prepare("
                SELECT ip.id as panel_id, ip.scheduled_at, jr.title as job_title, u.name as candidate_name,
                       COALESCE(fs.id, 0) as already_submitted
                FROM panel_members pm
                JOIN interview_panels ip ON pm.panel_id = ip.id
                JOIN job_requisitions jr ON ip.job_id = jr.id
                JOIN applications a ON ip.application_id = a.id
                JOIN users u ON a.candidate_id = u.id
                LEFT JOIN feedback_submissions fs ON fs.panel_id=ip.id AND fs.interviewer_id=:uid
                WHERE pm.user_id=:uid2 AND ip.status IN ('active','completed')
                ORDER BY ip.scheduled_at DESC
            ");
            $stmt->execute(["uid" => $uid, "uid2" => $uid]);
        }
        $panels = $stmt->fetchAll();
        $pending  = array_filter($panels, fn($p) => !$p["already_submitted"]);
        $done     = array_filter($panels, fn($p) =>  $p["already_submitted"]);

        $pageTitle = "Feedback";
        ob_start();
        ?>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Feedback</h1>
            <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-sm"><?= count($pending) ?> Pending</span>
        </div>

        <?php if (!empty($pending)): ?>
        <h2 class="text-base font-semibold text-slate-700 mb-3">Awaiting Your Feedback</h2>
        <div class="space-y-3 mb-8">
            <?php foreach ($pending as $p): ?>
            <div class="bg-white rounded-xl border p-5 flex justify-between items-center card-hover">
                <div>
                    <h3 class="font-semibold"><?= htmlspecialchars($p["job_title"]) ?></h3>
                    <p class="text-sm text-slate-500">Candidate: <?= htmlspecialchars($p["candidate_name"]) ?> &middot; Panel #<?= $p["panel_id"] ?> &middot; <?= $p["scheduled_at"] ?></p>
                </div>
                <div class="flex gap-2">
                    <?php if ($role === "interviewer"): ?>
                    <form method="POST" action="index.php?page=interviewer/feedback&action=end" onsubmit="return confirm('End this interview session? Candidates will be disconnected.')">
                        <?= $this->csrfField() ?>
                        <input type="hidden" name="panel_id" value="<?= $p["panel_id"] ?>">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition">
                            End Interview
                        </button>
                    </form>
                    <?php endif; ?>
                    <a href="index.php?page=interviewer/feedback&action=submit&panel_id=<?= $p["panel_id"] ?>"
                       class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                        Submit Feedback
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($done)): ?>
        <h2 class="text-base font-semibold text-slate-700 mb-3">Submitted</h2>
        <div class="space-y-2">
            <?php foreach ($done as $p): ?>
            <div class="bg-white rounded-xl border p-4 flex justify-between items-center opacity-60">
                <div>
                    <h3 class="font-medium text-sm"><?= htmlspecialchars($p["job_title"]) ?></h3>
                    <p class="text-xs text-slate-400">Candidate: <?= htmlspecialchars($p["candidate_name"]) ?> &middot; Panel #<?= $p["panel_id"] ?></p>
                </div>
                <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-100 text-emerald-700">Submitted</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($pending) && empty($done)): ?>
        <div class="text-center py-12 bg-white rounded-xl border">
            <p class="text-slate-500">No interview panels assigned to you yet.</p>
        </div>
        <?php endif; ?>
        <?php
        $content = ob_get_clean();
        $this->renderLayout($content, compact("pageTitle"));
    }

    // End interview — interviewer only, POST
    public function end(): void {
        $this->requireRole("interviewer");
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !$this->validateCsrf()) {
            $this->setFlash("error", "Invalid request.");
            $this->redirect("index.php?page=interviewer/feedback");
            return;
        }
        $panelId = (int)($_POST["panel_id"] ?? 0);
        if ($panelId < 1) {
            $this->setFlash("error", "Invalid panel.");
            $this->redirect("index.php?page=interviewer/feedback");
            return;
        }
        $db = Database::getInstance();
        $db->prepare("UPDATE interview_panels SET status='completed' WHERE id=:id AND status IN ('active','scheduled')")
           ->execute(["id" => $panelId]);
        AuditLogger::getInstance()->log((int)$this->currentUser["id"], "interview_panel", $panelId, "ended", [], []);
        $this->setFlash("success", "Interview ended. Please submit your feedback below.");
        $this->redirect("index.php?page=interviewer/feedback&action=submit&panel_id=$panelId");
    }

    // Submit feedback form + POST handler
    public function submit(): void {
        $this->requireRole(["interviewer","hr_admin","shadow"]);
        $panelId  = $this->getIntInput("panel_id");
        $role     = $this->currentUser["role"];
        $isShadow = ($role === "shadow");
        $db       = Database::getInstance();

        // Load panel info
        $pStmt = $db->prepare("
            SELECT ip.*, jr.title as job_title, u.id as cid, u.name as candidate_name, ip.application_id
            FROM interview_panels ip
            JOIN job_requisitions jr ON ip.job_id = jr.id
            JOIN applications a ON ip.application_id = a.id
            JOIN users u ON a.candidate_id = u.id
            WHERE ip.id = :id
        ");
        $pStmt->execute(["id" => $panelId]);
        $panel = $pStmt->fetch();
        if (!$panel) {
            $this->setFlash("error", "Panel #$panelId not found.");
            $this->redirect("index.php?page=interviewer/feedback");
            return;
        }

        // Check already submitted
        $existing = $db->prepare("SELECT id FROM feedback_submissions WHERE panel_id=:pid AND interviewer_id=:uid");
        $existing->execute(["pid" => $panelId, "uid" => $this->currentUser["id"]]);
        $alreadyDone = (bool)$existing->fetch();

        // Handle POST
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!$this->validateCsrf()) {
                $this->setFlash("error", "Security token expired. Please try again.");
                $this->redirect("index.php?page=interviewer/feedback&action=submit&panel_id=$panelId");
                return;
            }
            if ($alreadyDone) {
                $this->setFlash("error", "You have already submitted feedback for this panel.");
                $this->redirect("index.php?page=interviewer/feedback");
                return;
            }

            $score      = (float)($_POST["overall_score"] ?? 0);
            $comments   = trim($_POST["comments"] ?? "");
            $notes      = trim($_POST["overall_notes"] ?? "");
            $recommendation = trim($_POST["recommendation"] ?? "");

            if (empty($comments)) {
                $this->setFlash("error", "Comments are required.");
                $this->redirect("index.php?page=interviewer/feedback&action=submit&panel_id=$panelId");
                return;
            }

            $dims = [];
            foreach (FEEDBACK_DIMENSIONS as $dim) {
                $dims[$dim] = ["score" => (float)($_POST["dim_$dim"] ?? 0), "notes" => trim($_POST["notes_$dim"] ?? "")];
            }

            // Insert submission
            $subRole    = $isShadow ? "shadow" : ($role === "hr_admin" ? "hr_admin" : "interviewer");
            $includeScore = $isShadow ? 0 : 1;
            $db->prepare("
                INSERT INTO feedback_submissions
                    (panel_id, interviewer_id, candidate_id, score, comments, overall_notes, is_shadow, submitter_role, include_in_score, submitted_at)
                VALUES (:pid, :iid, :cid, :sc, :co, :no, :sh, :sr, :inc, NOW())
            ")->execute([
                "pid" => $panelId, "iid" => $this->currentUser["id"], "cid" => $panel["cid"],
                "sc" => $score, "co" => $comments, "no" => $notes,
                "sh" => $isShadow ? 1 : 0, "sr" => $subRole, "inc" => $includeScore
            ]);
            $subId = (int)$db->lastInsertId();

            // Dimension scores
            foreach ($dims as $dim => $d) {
                $db->prepare("INSERT INTO feedback_dimensions (submission_id, dimension, score, notes) VALUES (:sid, :d, :s, :n)")
                   ->execute(["sid" => $subId, "d" => $dim, "s" => $d["score"], "n" => $d["notes"]]);
            }

            // Normalized score (exclude shadow)
            $normStmt = $db->prepare("SELECT AVG(score) as avg FROM feedback_submissions WHERE panel_id=:pid AND include_in_score=1");
            $normStmt->execute(["pid" => $panelId]);
            $normalizedScore = round((float)($normStmt->fetch()["avg"] ?? 0), 2);

            // Hiring recommendation — computed or overridden by interviewer
            $allowed = ["strong_hire","hire","no_hire","strong_no_hire"];
            if ($role === "interviewer" && in_array($recommendation, $allowed)) {
                $rec = $recommendation;
            } else {
                $rec = $normalizedScore >= 8.5 ? "strong_hire" : ($normalizedScore >= 6.5 ? "hire" : ($normalizedScore >= 4.0 ? "no_hire" : "strong_no_hire"));
            }
            $db->prepare("
                INSERT INTO hiring_recommendations (application_id, recommendation, final_score, decided_by)
                VALUES (:aid, :rec, :sc, :uid)
                ON DUPLICATE KEY UPDATE recommendation=:rec2, final_score=:sc2, decided_by=:uid2
            ")->execute([
                "aid" => $panel["application_id"], "rec" => $rec, "sc" => $normalizedScore, "uid" => $this->currentUser["id"],
                "rec2" => $rec, "sc2" => $normalizedScore, "uid2" => $this->currentUser["id"]
            ]);

            // Mark panel completed if interviewer is the one finishing
            if ($role === "interviewer") {
                $db->prepare("UPDATE interview_panels SET status='completed' WHERE id=:id")->execute(["id" => $panelId]);
            }
            AuditLogger::getInstance()->log((int)$this->currentUser["id"], "feedback", $subId, "submitted", [], ["score" => $score, "rec" => $rec]);

            $recLabels = ["strong_hire"=>"Strong Hire","hire"=>"Hire","no_hire"=>"No Hire","strong_no_hire"=>"Strong No Hire"];

            // If AJAX call (from live room sidebar), return JSON
            if ($this->isAjax()) {
                $this->jsonResponse(["success"=>true,"normalized_score"=>$normalizedScore,"recommendation"=>$rec,"is_shadow"=>$isShadow]);
                return;
            }

            $this->setFlash("success", "Feedback submitted! Score: {$normalizedScore}/10 | Recommendation: " . ($recLabels[$rec] ?? $rec));
            $this->redirect("index.php?page=dashboard");
            return;
        }


        // --- GET: Render the form ---
        $pageTitle = "Submit Feedback — Panel #$panelId";
        ob_start();
        ?>
        <div class="max-w-2xl mx-auto">
            <?php if ($alreadyDone): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
                <p class="text-amber-700 font-semibold">You have already submitted feedback for Panel #<?= $panelId ?>.</p>
                <a href="index.php?page=interviewer/feedback" class="mt-3 inline-block text-indigo-600 hover:underline text-sm">Back to Feedback List</a>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-xl border p-6 mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-xl font-bold mb-1">Submit Feedback</h2>
                        <p class="text-slate-500 text-sm">Panel #<?= $panelId ?> &middot; <?= htmlspecialchars($panel["job_title"]) ?> &middot; <?= htmlspecialchars($panel["candidate_name"]) ?></p>
                    </div>
                    <?php if ($role === "interviewer"): ?>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-700">Technical Interviewer</span>
                    <?php elseif ($role === "hr_admin"): ?>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700">HR Admin</span>
                    <?php elseif ($isShadow): ?>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">Shadow Observer</span>
                    <?php endif; ?>
                </div>
                <?php if ($isShadow): ?>
                <div class="mt-3 p-3 bg-amber-50 rounded-lg text-sm text-amber-700 border border-amber-200">
                    Your feedback as a shadow observer is recorded but <strong>not included</strong> in the normalized score.
                </div>
                <?php endif; ?>
            </div>

            <form method="POST" action="index.php?page=interviewer/feedback&action=submit&panel_id=<?= $panelId ?>" class="space-y-4">
                <?= $this->csrfField() ?>

                <!-- Overall Score -->
                <div class="bg-white rounded-xl border p-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-3">Overall Score <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-4 mb-2">
                        <input type="range" name="overall_score" id="overallScore" min="0" max="10" step="0.5" value="7"
                               class="flex-1 h-2 accent-indigo-600"
                               oninput="document.getElementById('scoreVal').textContent=parseFloat(this.value).toFixed(1)">
                        <span class="w-12 text-center font-bold text-indigo-600 text-lg" id="scoreVal">7.0</span>
                        <span class="text-slate-400">/10</span>
                    </div>
                    <div class="flex justify-between text-xs text-slate-400">
                        <span>0 — Strong No Hire</span><span>5 — No Hire</span><span>7 — Hire</span><span>9 — Strong Hire</span>
                    </div>
                </div>

                <!-- Dimension Scores -->
                <?php foreach (FEEDBACK_DIMENSIONS as $dim):
                    $label = ucwords(str_replace("_", " ", $dim)); ?>
                <div class="bg-white rounded-xl border p-5">
                    <label class="block text-sm font-semibold text-slate-700 mb-2"><?= $label ?></label>
                    <div class="flex items-center gap-4">
                        <input type="range" name="dim_<?= $dim ?>" min="0" max="10" step="0.5" value="7"
                               class="flex-1 h-2 accent-purple-600"
                               oninput="document.getElementById('dv_<?= $dim ?>').textContent=parseFloat(this.value).toFixed(1)">
                        <span class="w-10 text-center font-bold text-purple-600 text-sm" id="dv_<?= $dim ?>">7.0</span>
                        <span class="text-slate-400 text-sm">/10</span>
                    </div>
                    <textarea name="notes_<?= $dim ?>" rows="2" class="mt-2 w-full px-3 py-2 border rounded-lg text-sm"
                              placeholder="Notes for <?= strtolower($label) ?>..."></textarea>
                </div>
                <?php endforeach; ?>

                <!-- Comments -->
                <div class="bg-white rounded-xl border p-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Comments <span class="text-red-500">*</span></label>
                    <textarea name="comments" rows="4" required class="w-full px-3 py-2 border rounded-lg text-sm"
                              placeholder="Overall evaluation of the candidate's performance..."></textarea>
                    <label class="block text-sm font-semibold text-slate-700 mt-4 mb-2">Private Notes</label>
                    <textarea name="overall_notes" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"
                              placeholder="Internal notes (not shared with candidate)..."></textarea>
                </div>

                <?php if ($role === "interviewer"): ?>
                <!-- Hiring Recommendation — Interviewer Only -->
                <div class="bg-white rounded-xl border p-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-3">
                        Hiring Recommendation <span class="text-red-500">*</span>
                        <span class="ml-2 text-xs text-slate-400 font-normal">(Technical Interviewer only)</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <?php
                        $recOptions = [
                            "strong_hire"    => ["label"=>"Strong Hire",    "desc"=>"Score 9-10","color"=>"peer-checked:bg-emerald-600 peer-checked:border-emerald-600 peer-checked:text-white"],
                            "hire"           => ["label"=>"Hire",           "desc"=>"Score 7-8", "color"=>"peer-checked:bg-green-500 peer-checked:border-green-500 peer-checked:text-white"],
                            "no_hire"        => ["label"=>"No Hire",        "desc"=>"Score 4-6", "color"=>"peer-checked:bg-amber-500 peer-checked:border-amber-500 peer-checked:text-white"],
                            "strong_no_hire" => ["label"=>"Strong No Hire", "desc"=>"Score 0-3", "color"=>"peer-checked:bg-red-600 peer-checked:border-red-600 peer-checked:text-white"],
                        ];
                        foreach ($recOptions as $val => $opt):
                        ?>
                        <label class="relative flex cursor-pointer">
                            <input type="radio" name="recommendation" value="<?= $val ?>" class="sr-only peer" required>
                            <span class="w-full text-center py-3 px-2 rounded-xl border-2 border-slate-200 text-sm font-semibold text-slate-600
                                         <?= $opt["color"] ?> hover:border-slate-400 transition-all">
                                <span class="block"><?= $opt["label"] ?></span>
                                <span class="block text-xs font-normal opacity-75"><?= $opt["desc"] ?></span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition text-lg">
                    Submit Feedback &amp; Return to Dashboard
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php
        $content = ob_get_clean();
        $this->renderLayout($content, compact("pageTitle"));
    }

    // Gap Analysis — radar chart of all feedback for an application
    public function gap(): void {
        $this->requireRole(["interviewer","hr_admin","shadow"]);
        $appId = $this->getIntInput("id");
        $db    = Database::getInstance();

        // Get application + all feedback submissions
        $appStmt = $db->prepare("
            SELECT a.*, jr.title as job_title, u.name as candidate_name
            FROM applications a
            JOIN job_requisitions jr ON a.job_id = jr.id
            JOIN users u ON a.candidate_id = u.id
            WHERE a.id = :id
        ");
        $appStmt->execute(["id" => $appId]);
        $app = $appStmt->fetch();
        if (!$app) { $this->setFlash("error","Application not found"); $this->redirect("index.php?page=interviewer/feedback"); return; }

        // All feedback submissions for this application's panels
        $fbStmt = $db->prepare("
            SELECT fs.*, u.name as interviewer_name, fs.submitter_role,
                   ip.id as panel_id, fs.score, fs.include_in_score, fs.is_shadow
            FROM feedback_submissions fs
            JOIN interview_panels ip ON fs.panel_id = ip.id
            JOIN users u ON fs.interviewer_id = u.id
            WHERE ip.application_id = :aid
            ORDER BY fs.submitted_at DESC
        ");
        $fbStmt->execute(["aid" => $appId]);
        $feedbacks = $fbStmt->fetchAll();

        // Dimension averages
        $dims = FEEDBACK_DIMENSIONS;
        $dimTotals = array_fill_keys($dims, ["sum"=>0,"count"=>0]);
        foreach ($feedbacks as $fb) {
            if (!$fb["include_in_score"]) continue;
            $dimStmt = $db->prepare("SELECT dimension, score FROM feedback_dimensions WHERE submission_id=:sid");
            $dimStmt->execute(["sid"=>$fb["id"]]);
            foreach ($dimStmt->fetchAll() as $d) {
                if (isset($dimTotals[$d["dimension"]])) {
                    $dimTotals[$d["dimension"]]["sum"] += $d["score"];
                    $dimTotals[$d["dimension"]]["count"]++;
                }
            }
        }
        $dimAvgs = [];
        foreach ($dims as $dim) {
            $dimAvgs[$dim] = $dimTotals[$dim]["count"] > 0
                ? round($dimTotals[$dim]["sum"] / $dimTotals[$dim]["count"], 2) : 0;
        }

        // Hiring recommendation
        $recStmt = $db->prepare("SELECT * FROM hiring_recommendations WHERE application_id=:aid ORDER BY created_at DESC LIMIT 1");
        $recStmt->execute(["aid"=>$appId]);
        $rec = $recStmt->fetch();

        $normalizedScore = count(array_filter($feedbacks, fn($f) => $f["include_in_score"])) > 0
            ? round(array_sum(array_column(array_filter($feedbacks, fn($f)=>$f["include_in_score"]),"score")) / count(array_filter($feedbacks,fn($f)=>$f["include_in_score"])),2)
            : 0;

        $recColors = ["strong_hire"=>"bg-emerald-100 text-emerald-700","hire"=>"bg-green-100 text-green-700","no_hire"=>"bg-amber-100 text-amber-700","strong_no_hire"=>"bg-red-100 text-red-700"];
        $recLabels = ["strong_hire"=>"Strong Hire","hire"=>"Hire","no_hire"=>"No Hire","strong_no_hire"=>"Strong No Hire"];

        $pageTitle = "Gap Analysis — " . $app["candidate_name"];
        ob_start();
        ?>
        <div class="max-w-5xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold"><?= htmlspecialchars($app["candidate_name"]) ?></h1>
                    <p class="text-slate-500 text-sm"><?= htmlspecialchars($app["job_title"]) ?> &middot; Application #<?= $appId ?></p>
                </div>
                <?php if ($rec): ?>
                <span class="px-4 py-2 rounded-full font-semibold text-sm <?= $recColors[$rec["recommendation"]] ?? "bg-slate-100 text-slate-600" ?>">
                    <?= $recLabels[$rec["recommendation"]] ?? ucfirst($rec["recommendation"]) ?> &middot; <?= $normalizedScore ?>/10
                </span>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Radar Chart -->
                <div class="bg-white rounded-xl border p-6">
                    <h3 class="font-semibold mb-4 text-slate-800">Competency Radar</h3>
                    <div class="relative" style="height:300px">
                        <canvas id="radarChart"></canvas>
                    </div>
                </div>

                <!-- Score Summary -->
                <div class="bg-white rounded-xl border p-6">
                    <h3 class="font-semibold mb-4 text-slate-800">Dimension Breakdown</h3>
                    <div class="space-y-3">
                        <?php foreach ($dims as $dim): $avg = $dimAvgs[$dim]; $pct = ($avg / 10) * 100; ?>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-700 font-medium"><?= ucwords(str_replace("_"," ",$dim)) ?></span>
                                <span class="font-bold <?= $avg >= 8 ? "text-emerald-600" : ($avg >= 6 ? "text-indigo-600" : ($avg >= 4 ? "text-amber-600" : "text-red-600")) ?>"><?= $avg ?>/10</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full <?= $avg >= 8 ? "bg-emerald-500" : ($avg >= 6 ? "bg-indigo-500" : ($avg >= 4 ? "bg-amber-500" : "bg-red-500")) ?>"
                                     style="width:<?= $pct ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="pt-3 border-t mt-3">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-slate-700">Normalized Score</span>
                                <span class="text-2xl font-bold text-indigo-600"><?= $normalizedScore ?><span class="text-sm text-slate-400">/10</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Individual Feedback Cards -->
            <h3 class="font-semibold text-slate-700 mb-3">Individual Evaluations (<?= count($feedbacks) ?>)</h3>
            <div class="space-y-3">
                <?php foreach ($feedbacks as $fb): ?>
                <div class="bg-white rounded-xl border p-5 <?= !$fb["include_in_score"] ? "opacity-60" : "" ?>">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <p class="font-semibold text-sm"><?= htmlspecialchars($fb["interviewer_name"]) ?></p>
                            <p class="text-xs text-slate-400"><?= ucfirst(str_replace("_"," ",$fb["submitter_role"])) ?> &middot; Panel #<?= $fb["panel_id"] ?></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ($fb["is_shadow"]): ?><span class="px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-700">Shadow</span><?php endif; ?>
                            <span class="text-lg font-bold text-indigo-600"><?= $fb["score"] ?>/10</span>
                        </div>
                    </div>
                    <p class="text-sm text-slate-600 bg-slate-50 rounded-lg p-3"><?= htmlspecialchars($fb["comments"]) ?></p>
                </div>
                <?php endforeach; ?>
                <?php if (empty($feedbacks)): ?>
                <div class="text-center py-10 bg-white rounded-xl border text-slate-400">No feedback submitted yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <script>
        // Chart.js is already loaded by the main layout head — just render the radar
        document.addEventListener("DOMContentLoaded", function() {
            const canvas = document.getElementById("radarChart");
            if (!canvas) return;
            const ctx = canvas.getContext("2d");
            new Chart(ctx, {
                type: "radar",
                data: {
                    labels: <?= json_encode(array_map(fn($d)=>ucwords(str_replace("_"," ",$d)), $dims)) ?>,
                    datasets: [{
                        label: "Avg Score",
                        data: <?= json_encode(array_values($dimAvgs)) ?>,
                        backgroundColor: "rgba(99,102,241,0.12)",
                        borderColor: "rgba(99,102,241,0.9)",
                        borderWidth: 2.5,
                        pointBackgroundColor: "#4f46e5",
                        pointBorderColor: "#fff",
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 600 },
                    scales: {
                        r: {
                            min: 0, max: 10,
                            ticks: { stepSize: 2, font:{size:10}, backdropColor:"transparent" },
                            grid: { color: "rgba(0,0,0,0.07)" },
                            angleLines: { color: "rgba(0,0,0,0.07)" },
                            pointLabels: { font:{size:12, weight:"600"}, color:"#334155", padding:8 }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => " " + ctx.raw.toFixed(1) + " / 10"
                            }
                        }
                    }
                }
            });
        });
        </script>
        <?php
        $content = ob_get_clean();
        $this->renderLayout($content, compact("pageTitle"));
    }
}