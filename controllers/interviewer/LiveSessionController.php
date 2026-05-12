<?php declare(strict_types=1);
class LiveSessionController extends BaseController {

    /** Unified entry for all roles: ?page=interviewer/live&action=join&id=PANEL_ID */
    public function join(): void {
        $this->requireRole(["interviewer","hr_admin","shadow","candidate"]);
        $panelId = $this->getIntInput("id");
        $db = Database::getInstance();
        $role = $this->currentUser["role"];

        // Fetch panel with candidate name
        $stmt = $db->prepare("
            SELECT ip.*, jr.title as job_title,
                   u.name as candidate_name, u.id as candidate_id,
                   a.id as application_id
            FROM interview_panels ip
            JOIN job_requisitions jr ON ip.job_id = jr.id
            JOIN applications a ON ip.application_id = a.id
            JOIN users u ON a.candidate_id = u.id
            WHERE ip.id = :id
        ");
        $stmt->execute(["id" => $panelId]);
        $panel = $stmt->fetch();

        if (!$panel) {
            $this->setFlash("error", "Interview panel #$panelId not found.");
            $this->redirect("index.php?page=dashboard");
            return;
        }

        // Candidate: must own the application
        if ($role === "candidate") {
            if ((int)$panel["candidate_id"] !== (int)$this->currentUser["id"]) {
                $this->setFlash("error", "You are not the candidate for this interview.");
                $this->redirect("index.php?page=dashboard");
                return;
            }
        }

        // Non-candidate: must be a panel member OR hr_admin
        if ($role !== "candidate" && $role !== "hr_admin") {
            $pm = $db->prepare("SELECT id FROM panel_members WHERE panel_id=:pid AND user_id=:uid");
            $pm->execute(["pid" => $panelId, "uid" => $this->currentUser["id"]]);
            if (!$pm->fetch()) {
                $this->setFlash("error", "You are not assigned to panel #$panelId.");
                $this->redirect("index.php?page=dashboard");
                return;
            }
        }

        // Activate panel if still scheduled
        $db->prepare("UPDATE interview_panels SET status='active' WHERE id=:id AND status='scheduled'")
           ->execute(["id" => $panelId]);

        // Ensure live_session row exists
        $db->prepare("INSERT IGNORE INTO live_sessions (panel_id, current_code, language) VALUES (:pid, '// Write your solution here...', :lang)")
           ->execute(["pid" => $panelId, "lang" => $panel["coding_language"] ?? "javascript"]);

        $sessStmt = $db->prepare("SELECT * FROM live_sessions WHERE panel_id=:pid");
        $sessStmt->execute(["pid" => $panelId]);
        $sess = $sessStmt->fetch();

        $isCandidate = ($role === "candidate");
        $canSetLang  = in_array($role, ["interviewer","hr_admin"]);
        $canExtend   = in_array($role, ["interviewer","hr_admin"]);
        $langs = defined("CODING_LANGUAGE_LABELS") ? CODING_LANGUAGE_LABELS : [
            "javascript"=>"JavaScript","python"=>"Python","java"=>"Java",
            "cpp"=>"C++","php"=>"PHP","go"=>"Go","rust"=>"Rust","typescript"=>"TypeScript"
        ];
        $currentLang = $sess["language"] ?? "javascript";
        $csrf = $this->generateCsrf();
        $totalMinutes = (int)$panel["duration_minutes"] + (int)$panel["extended_by_minutes"];

        $pageTitle = "Interview Room — " . $panel["job_title"];
        ob_start();
        ?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
* { font-family: "Inter", sans-serif; }
#codeEditor { font-family: "JetBrains Mono", monospace !important; }
.tab-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.97); z-index: 9999; display: none; align-items: center; justify-content: center; flex-direction: column; }
.tab-overlay.show { display: flex; }
</style>
</head>
<body class="bg-slate-900 text-white h-screen flex flex-col overflow-hidden">

<?php if ($isCandidate): ?>
<!-- CANDIDATE TAB-SWITCH FULL-PAGE OVERLAY -->
<div class="tab-overlay" id="tabOverlay">
    <div class="text-center max-w-lg px-6">
        <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-red-600 flex items-center justify-center">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-red-400 mb-3">Tab Switch Detected</h1>
        <p class="text-slate-300 text-lg mb-4">You left the interview window. This has been recorded.</p>
        <div class="bg-red-900/50 border border-red-700 rounded-xl p-4 mb-6">
            <p class="text-red-300 font-semibold text-xl">Strike <span id="strikeNum">0</span> of 3</p>
            <p class="text-red-400 text-sm mt-1">After 3 strikes your interview will be auto-flagged.</p>
        </div>
        <button onclick="dismissOverlay()" class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 rounded-xl font-semibold text-lg transition">
            Return to Interview
        </button>
    </div>
</div>
<?php endif; ?>

<!-- TOP BAR -->
<div class="bg-slate-800 border-b border-slate-700 px-4 py-2 flex items-center gap-4 flex-shrink-0">
    <div class="flex-1">
        <p class="font-semibold text-sm"><?= htmlspecialchars($panel["job_title"]) ?></p>
        <p class="text-xs text-slate-400">Panel #<?= $panelId ?> &middot; Candidate: <?= htmlspecialchars($panel["candidate_name"]) ?></p>
    </div>

    <!-- Language selector (interviewer / hr_admin only) -->
    <?php if ($canSetLang): ?>
    <select id="langSelect" class="bg-slate-700 border border-slate-600 rounded-lg px-3 py-1.5 text-sm font-mono" onchange="setLanguage(this.value)">
        <?php foreach ($langs as $k => $l): ?>
        <option value="<?= $k ?>" <?= $currentLang===$k ? "selected" : "" ?>><?= $l ?></option>
        <?php endforeach; ?>
    </select>
    <?php else: ?>
    <span class="bg-slate-700 border border-slate-600 rounded-lg px-3 py-1.5 text-sm font-mono" id="langDisplay">
        <?= $langs[$currentLang] ?? $currentLang ?>
    </span>
    <?php endif; ?>

    <!-- Timer -->
    <div class="bg-slate-900 border border-slate-600 rounded-lg px-4 py-1.5 font-mono text-lg font-bold" id="timerDisplay">
        <?= str_pad((string)$totalMinutes, 2, "0", STR_PAD_LEFT) ?>:00
    </div>

    <!-- Extend / Request Extension -->
    <?php if ($canExtend): ?>
    <?php if ($role === "interviewer"): ?>
    <!-- Interviewer: request needs HR sign-off -->
    <button onclick="openExtendModal()" id="extendBtn"
            class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 rounded-lg text-sm font-medium transition flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Request Extension
    </button>
    <!-- Extension modal -->
    <div id="extendModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div class="bg-slate-800 rounded-2xl p-6 w-full max-w-md border border-slate-600">
            <h3 class="text-lg font-bold mb-1">Request Time Extension</h3>
            <p class="text-slate-400 text-sm mb-4">HR Admin will be notified and must approve before time is added.</p>
            <label class="block text-xs text-slate-400 mb-1">Additional Minutes</label>
            <select id="extMinutes" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 mb-3 text-sm">
                <option value="5">+5 minutes</option>
                <option value="10" selected>+10 minutes</option>
                <option value="15">+15 minutes</option>
                <option value="30">+30 minutes</option>
            </select>
            <label class="block text-xs text-slate-400 mb-1">Reason <span class="text-red-400">*</span></label>
            <textarea id="extReason" rows="2" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 mb-4 text-sm resize-none"
                      placeholder="e.g. Technical issues with the coding environment..."></textarea>
            <div class="flex gap-3">
                <button onclick="submitExtensionRequest()" class="flex-1 py-2 bg-amber-600 hover:bg-amber-700 rounded-lg text-sm font-semibold transition">Send to HR Admin</button>
                <button onclick="closeExtendModal()" class="flex-1 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm font-semibold transition">Cancel</button>
            </div>
            <p id="extResult" class="text-xs text-center mt-3 hidden"></p>
        </div>
    </div>
    <?php else: ?>
    <!-- HR Admin: instant extend -->
    <button onclick="extendSession()" id="extendBtn"
            class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 rounded-lg text-sm font-medium transition">
        +10 min
    </button>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Role badge -->
    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $isCandidate ? 'bg-emerald-700 text-emerald-100' : 'bg-slate-600 text-slate-200' ?>">
        <?= $isCandidate ? 'Candidate' : ucfirst(str_replace('_',' ',$role)) ?>
        <?= !$isCandidate ? ' (Read Only)' : '' ?>
    </span>

    <!-- Sync indicator -->
    <div class="flex items-center gap-1.5 text-xs text-slate-400">
        <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" id="syncDot"></div>
        <span>Live</span>
    </div>
</div>

<!-- MAIN AREA: Editor + Sidebar -->
<div class="flex flex-1 overflow-hidden">

    <!-- Code Editor -->
    <div class="flex-1 relative">
        <textarea id="codeEditor"
            class="absolute inset-0 w-full h-full p-4 bg-slate-950 text-green-300 text-sm resize-none outline-none leading-relaxed"
            <?= $isCandidate ? '' : 'readonly' ?>
            spellcheck="false"
            placeholder="// Code will appear here..."><?= htmlspecialchars($sess["current_code"] ?? "") ?></textarea>
        <?php if (!$isCandidate): ?>
        <div class="absolute top-2 right-2 bg-slate-700 text-slate-300 text-xs px-2 py-1 rounded opacity-70">Read Only</div>
        <?php endif; ?>
    </div>

    <!-- Right Sidebar -->
    <div class="w-72 bg-slate-800 border-l border-slate-700 flex flex-col overflow-y-auto">

        <!-- Participants -->
        <div class="p-4 border-b border-slate-700">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Participants</p>
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-sm">
                    <div class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold">
                        <?= strtoupper(substr($this->currentUser["name"], 0, 1)) ?>
                    </div>
                    <div class="flex-1">
                        <p class="text-slate-200 text-sm"><?= htmlspecialchars($this->currentUser["name"]) ?></p>
                        <p class="text-slate-500 text-xs"><?= ucfirst(str_replace("_"," ",$role)) ?></p>
                    </div>
                    <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
                </div>
            </div>
        </div>

        <!-- Feedback section — all roles -->
        <?php if (!$isCandidate): ?>
        <div class="p-4 border-b border-slate-700">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Quick Feedback</p>
            <?php if ($role === "shadow"): ?>
            <div class="text-xs text-amber-400 bg-amber-900/30 border border-amber-700 rounded-lg p-2 mb-3">
                Shadow — score not counted in final.
            </div>
            <?php endif; ?>
            <div class="space-y-3" id="feedbackPanel">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Overall Score</label>
                    <div class="flex items-center gap-2">
                        <input type="range" id="fbScore" min="0" max="10" step="0.5" value="7" class="flex-1 accent-indigo-500" oninput="document.getElementById('fbScoreVal').textContent=parseFloat(this.value).toFixed(1)">
                        <span class="text-sm font-bold text-indigo-400 w-10 text-center" id="fbScoreVal">7.0</span>
                    </div>
                </div>
                <?php foreach (["coding","system_design","communication","culture_fit"] as $dim): ?>
                <div>
                    <label class="block text-xs text-slate-400 mb-1"><?= ucwords(str_replace("_"," ",$dim)) ?></label>
                    <div class="flex items-center gap-2">
                        <input type="range" id="dim_<?= $dim ?>" min="0" max="10" step="0.5" value="7" class="flex-1 accent-purple-500" oninput="document.getElementById('dv_<?= $dim ?>').textContent=parseFloat(this.value).toFixed(1)">
                        <span class="text-xs font-bold text-purple-400 w-8 text-center" id="dv_<?= $dim ?>">7.0</span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if ($role === "interviewer"): ?>
                <!-- Hiring Recommendation — Interviewer only -->
                <div>
                    <label class="block text-xs text-slate-400 mb-2">Recommendation <span class="text-red-400">*</span></label>
                    <div class="grid grid-cols-2 gap-1.5">
                        <?php foreach (["strong_hire"=>"Strong Hire","hire"=>"Hire","no_hire"=>"No Hire","strong_no_hire"=>"Strong No Hire"] as $rv=>$rl): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="fbRec" value="<?= $rv ?>" class="sr-only peer">
                            <span class="block text-center py-1.5 px-1 rounded-lg border border-slate-600 text-xs font-medium text-slate-400
                                         peer-checked:bg-indigo-600 peer-checked:border-indigo-600 peer-checked:text-white
                                         hover:border-slate-400 transition-all cursor-pointer"><?= $rl ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Comments <span class="text-red-400">*</span></label>
                    <textarea id="fbComments" rows="3" class="w-full bg-slate-900 border border-slate-600 rounded-lg px-2 py-1.5 text-sm text-slate-200 resize-none" placeholder="Feedback comments..."></textarea>
                </div>
                <button onclick="submitFeedback()" id="fbBtn" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-sm font-semibold transition">
                    Submit Feedback
                </button>
                <p id="fbResult" class="text-xs text-center hidden"></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- End Interview — Interviewer only -->
        <?php if ($role === "interviewer"): ?>
        <div class="p-4 border-b border-slate-700">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Session Control</p>
            <button onclick="endInterview()" id="endBtn"
                class="w-full py-2 bg-red-700 hover:bg-red-600 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3v6m3-3H9"/>
                </svg>
                End Interview
            </button>
            <p class="text-xs text-slate-500 mt-1.5 text-center">Marks session complete &amp; goes to feedback</p>
        </div>
        <?php endif; ?>

        <!-- Session Info -->
        <div class="p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Session Info</p>
            <div class="space-y-1 text-xs text-slate-400">
                <p>Duration: <span class="text-slate-200"><?= $totalMinutes ?> min</span></p>
                <p>Status: <span class="text-emerald-400"><?= ucfirst($panel["status"]) ?></span></p>
                <p>Language: <span class="text-slate-200 font-mono" id="langInfo"><?= $langs[$currentLang] ?? $currentLang ?></span></p>
            </div>
        </div>
    </div>
</div>

<script>
const panelId   = <?= $panelId ?>;
const CSRF      = <?= json_encode($csrf) ?>;
const CSRF_NAME = <?= json_encode(CSRF_TOKEN_NAME) ?>;
const isCandidate = <?= $isCandidate ? 'true' : 'false' ?>;
const role      = <?= json_encode($role) ?>;
const editor    = document.getElementById("codeEditor");
let lastCode    = editor.value;
let lastPush    = 0;
let strikes     = 0;
let totalSecs   = <?= $totalMinutes * 60 ?>;

// ── Timer ──────────────────────────────────────────────────────────
const timerEl = document.getElementById("timerDisplay");
const timerTick = setInterval(function() {
    if (totalSecs <= 0) { clearInterval(timerTick); timerEl.textContent = "00:00"; timerEl.classList.add("text-red-400"); return; }
    totalSecs--;
    const m = Math.floor(totalSecs / 60), s = totalSecs % 60;
    timerEl.textContent = String(m).padStart(2,"0") + ":" + String(s).padStart(2,"0");
    if (totalSecs < 300) timerEl.classList.add("text-amber-400");
    if (totalSecs < 60)  timerEl.classList.add("text-red-400","animate-pulse");
}, 1000);

// ── Code push (candidate only) ─────────────────────────────────────
<?php if ($isCandidate): ?>
editor.addEventListener("input", function() {
    lastPush = Date.now();
    clearTimeout(window._pushTimer);
    window._pushTimer = setTimeout(pushCode, 600);
});
function pushCode() {
    fetch("index.php?page=interviewer/live&action=push", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "panel_id=" + panelId + "&code=" + encodeURIComponent(editor.value) + "&" + CSRF_NAME + "=" + CSRF
    }).then(() => { document.getElementById("syncDot").style.background = "#34d399"; });
}
<?php endif; ?>

// ── Poll for code + language updates (all roles) ───────────────────
setInterval(function() {
    fetch("index.php?page=interviewer/live&action=poll&panel_id=" + panelId)
    .then(r => r.json()).then(d => {
        document.getElementById("syncDot").style.background = "#34d399";
        if (d.code !== undefined && d.code !== lastCode) {
            <?php if (!$isCandidate): ?>
            editor.value = d.code;
            <?php else: ?>
            if (Date.now() - lastPush > 2000) editor.value = d.code;
            <?php endif; ?>
            lastCode = d.code;
        }
        if (d.language) {
            document.getElementById("langInfo").textContent = d.language_label || d.language;
            <?php if (!$canSetLang): ?>
            const ld = document.getElementById("langDisplay");
            if (ld) ld.textContent = d.language_label || d.language;
            <?php endif; ?>
        }
    }).catch(() => { document.getElementById("syncDot").style.background = "#f87171"; });
}, 2000);

// ── Language change (interviewer / hr_admin) ───────────────────────
<?php if ($canSetLang): ?>
function setLanguage(lang) {
    fetch("index.php?page=interviewer/live&action=set_language", {
        method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body: "panel_id=" + panelId + "&language=" + lang + "&" + CSRF_NAME + "=" + CSRF
    }).then(r => r.json()).then(d => {
        if (d.ok) document.getElementById("langInfo").textContent = d.label || lang;
    });
}
<?php endif; ?>

// ── Extend session ─────────────────────────────────────────────────
<?php if ($canExtend): ?>
<?php if ($role === "interviewer"): ?>
function openExtendModal()  { document.getElementById("extendModal").classList.remove("hidden"); }
function closeExtendModal() { document.getElementById("extendModal").classList.add("hidden"); }
async function submitExtensionRequest() {
    const reason = document.getElementById("extReason").value.trim();
    if (!reason) { alert("Please provide a reason for the extension."); return; }
    const minutes = document.getElementById("extMinutes").value;
    const res = document.getElementById("extResult");
    res.classList.remove("hidden");
    res.textContent = "Sending request to HR Admin...";
    res.className = "text-xs text-center mt-3 text-amber-400";
    try {
        const r = await fetch("index.php?page=interviewer/live&action=request_extension", {
            method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body:"panel_id="+panelId+"&minutes="+minutes+"&reason="+encodeURIComponent(reason)+"&"+CSRF_NAME+"="+CSRF
        });
        const d = await r.json();
        if (d.ok) {
            res.textContent = d.message || "Request sent! Waiting for HR Admin approval.";
            res.className = "text-xs text-center mt-3 text-emerald-400";
            document.getElementById("extendBtn").textContent = "Requested";
            document.getElementById("extendBtn").disabled = true;
            setTimeout(() => closeExtendModal(), 3000);
        } else {
            res.textContent = d.error || "Failed to send request.";
            res.className = "text-xs text-center mt-3 text-red-400";
        }
    } catch(e) {
        res.textContent = "Network error. Please try again.";
        res.className = "text-xs text-center mt-3 text-red-400";
    }
}
<?php else: ?>
function extendSession() {
    const btn = document.getElementById("extendBtn");
    btn.disabled = true; btn.textContent = "Extending...";
    fetch("index.php?page=interviewer/live&action=extend", {
        method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body: "panel_id="+panelId+"&minutes=10&"+CSRF_NAME+"="+CSRF
    }).then(r=>r.json()).then(d=>{
        btn.disabled = false; btn.textContent = "+10 min";
        if (d.ok) { totalSecs += 600; }
    });
}
<?php endif; ?>
<?php endif; ?>

// ── Feedback submission (all non-candidate roles) ──────────────────
<?php if (!$isCandidate): ?>
async function submitFeedback() {
    const comments = document.getElementById("fbComments").value.trim();
    if (!comments) { alert("Please enter feedback comments."); return; }
    <?php if ($role === "interviewer"): ?>
    const recEl = document.querySelector('input[name="fbRec"]:checked');
    if (!recEl) { alert("Please select a hiring recommendation."); return; }
    const recommendation = recEl.value;
    <?php endif; ?>
    const btn = document.getElementById("fbBtn");
    btn.disabled = true; btn.textContent = "Submitting...";
    const score = document.getElementById("fbScore").value;
    const dims = ["coding","system_design","communication","culture_fit"];
    let body = "panel_id=" + panelId + "&overall_score=" + score + "&comments=" + encodeURIComponent(comments) + "&" + CSRF_NAME + "=" + CSRF;
    dims.forEach(d => { body += "&dim_" + d + "=" + document.getElementById("dim_"+d).value; });
    <?php if ($role === "shadow"): ?>body += "&shadow=1";<?php endif; ?>
    <?php if ($role === "interviewer"): ?>body += "&recommendation=" + recommendation;<?php endif; ?>
    try {
        const r = await fetch("index.php?page=interviewer/feedback&action=submit&panel_id=" + panelId, {
            method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"}, body
        });
        const data = await r.json();
        const res = document.getElementById("fbResult");
        res.classList.remove("hidden");
        if (data.success) {
            res.className = "text-xs text-center text-emerald-400";
            const recLabel = {"strong_hire":"Strong Hire","hire":"Hire","no_hire":"No Hire","strong_no_hire":"Strong No Hire"};
            res.textContent = "Submitted! Score: " + data.normalized_score + "/10"
                + (data.recommendation ? " | " + (recLabel[data.recommendation]||data.recommendation) : "")
                + (data.is_shadow ? " (shadow)" : "");
            btn.textContent = "Submitted";
            // Redirect to dashboard after 2s
            setTimeout(() => { window.location.href = "index.php?page=dashboard"; }, 2000);
        } else {
            res.className = "text-xs text-center text-red-400";
            res.textContent = data.error || "Submission failed.";
            btn.disabled = false; btn.textContent = "Submit Feedback";
        }
    } catch(e) {
        const res = document.getElementById("fbResult");
        res.classList.remove("hidden");
        res.className = "text-xs text-center text-red-400";
        res.textContent = "Network error. Please try again.";
        btn.disabled = false; btn.textContent = "Submit Feedback";
    }
}

<?php if ($role === "interviewer"): ?>
async function endInterview() {
    if (!confirm("End this interview session? This will mark it complete and take you to the feedback form.")) return;
    const btn = document.getElementById("endBtn");
    btn.disabled = true; btn.textContent = "Ending...";
    try {
        const r = await fetch("index.php?page=interviewer/live&action=end_session", {
            method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body: "panel_id=" + panelId + "&" + CSRF_NAME + "=" + CSRF
        });
        const d = await r.json();
        if (d.ok) {
            window.location.href = "index.php?page=interviewer/feedback&action=submit&panel_id=" + panelId;
        } else {
            btn.disabled = false; btn.textContent = "End Interview";
            alert(d.error || "Could not end session.");
        }
    } catch(e) {
        btn.disabled = false; btn.textContent = "End Interview";
        alert("Network error.");
    }
}
<?php endif; ?>
<?php endif; ?>


// ── Tab-switch detection (candidate only — full-page overlay) ──────
<?php if ($isCandidate): ?>
const overlay = document.getElementById("tabOverlay");
const MAX_STRIKES = 3;
function dismissOverlay() {
    if (strikes >= MAX_STRIKES) return; // can't dismiss after max
    overlay.classList.remove("show");
    editor.focus();
}
function showOverlay() {
    strikes++;
    document.getElementById("strikeNum").textContent = strikes;
    overlay.classList.add("show");
    // Notify server
    fetch("index.php?page=interviewer/live&action=flag_candidate&panel_id=" + panelId + "&strikes=" + strikes + "&" + CSRF_NAME + "=" + CSRF, {method:"POST"}).catch(()=>{});
    if (strikes >= MAX_STRIKES) {
        // Replace overlay content with termination message
        overlay.innerHTML = `
        <div class="text-center max-w-lg px-6">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-red-700 flex items-center justify-center">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
            <h1 class="text-3xl font-bold text-red-400 mb-3">Interview Terminated</h1>
            <p class="text-slate-300 text-lg mb-6">You have reached the maximum number of tab switch violations (3/3). Your interview session has been flagged and terminated automatically.</p>
            <p class="text-slate-400 text-sm">The HR Admin has been notified. You will be redirected in <span id="countdown">10</span> seconds.</p>
        </div>`;
        let secs = 10;
        const cd = setInterval(() => {
            secs--;
            const el = document.getElementById("countdown");
            if (el) el.textContent = secs;
            if (secs <= 0) { clearInterval(cd); window.location.href = "index.php?page=dashboard"; }
        }, 1000);
    }
}
document.addEventListener("visibilitychange", function() { if (document.hidden) showOverlay(); });
window.addEventListener("blur", function() { if (!document.hasFocus()) showOverlay(); });
document.addEventListener("contextmenu", e => e.preventDefault());
document.addEventListener("keydown", function(e) {
    if ((e.ctrlKey||e.metaKey) && ["c","v","u","s"].includes(e.key.toLowerCase())) e.preventDefault();
});
<?php endif; ?>
</script>
</body></html>
<?php
        $content = ob_get_clean();
        // Output directly (no sidebar layout for interview room — full screen)
        echo $content;
    }

    // ── AJAX: push code ───────────────────────────────────────────────
    public function push(): void {
        $this->requireRole(["candidate","interviewer","hr_admin","shadow"]);
        if (!$this->validateCsrf()) { $this->jsonResponse(["error"=>"csrf"],403); return; }
        $panelId = $this->getIntInput("panel_id");
        $code    = $_POST["code"] ?? "";
        Database::getInstance()
            ->prepare("UPDATE live_sessions SET current_code=:c WHERE panel_id=:pid")
            ->execute(["c"=>$code,"pid"=>$panelId]);
        $this->jsonResponse(["ok"=>true]);
    }

    // ── AJAX: poll latest code + language + remaining time ────────────
    public function poll(): void {
        $panelId = $this->getIntInput("panel_id");
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT ls.current_code as code, ls.language, ip.duration_minutes, ip.extended_by_minutes, ip.scheduled_at FROM live_sessions ls JOIN interview_panels ip ON ls.panel_id=ip.id WHERE ls.panel_id=:pid");
        $stmt->execute(["pid"=>$panelId]);
        $row = $stmt->fetch();
        if (!$row) { $this->jsonResponse(["code"=>"","language"=>"javascript","remaining_seconds"=>0]); return; }
        $labels = defined("CODING_LANGUAGE_LABELS") ? CODING_LANGUAGE_LABELS : [];
        $totalMins = (int)$row["duration_minutes"] + (int)$row["extended_by_minutes"];
        $startedAt = strtotime($row["scheduled_at"]);
        $elapsed   = max(0, time() - $startedAt);
        $remaining = max(0, ($totalMins * 60) - $elapsed);
        $this->jsonResponse([
            "code"             => $row["code"],
            "language"         => $row["language"],
            "language_label"   => $labels[$row["language"]] ?? $row["language"],
            "remaining_seconds"=> $remaining,
            "total_minutes"    => $totalMins,
        ]);
    }

    // ── AJAX: set language ────────────────────────────────────────────
    public function set_language(): void {
        $this->requireRole(["interviewer","hr_admin"]);
        if (!$this->validateCsrf()) { $this->jsonResponse(["error"=>"csrf"],403); return; }
        $panelId = $this->getIntInput("panel_id");
        $lang    = $this->getInput("language");
        $allowed = defined("CODING_LANGUAGES") ? CODING_LANGUAGES : ["javascript","python","java","cpp","php","go","rust","typescript","csharp"];
        if (!in_array($lang, $allowed)) { $this->jsonResponse(["error"=>"Invalid language"],400); return; }
        $db = Database::getInstance();
        $db->prepare("UPDATE live_sessions SET language=:l WHERE panel_id=:pid")->execute(["l"=>$lang,"pid"=>$panelId]);
        $db->prepare("UPDATE interview_panels SET coding_language=:l WHERE id=:pid")->execute(["l"=>$lang,"pid"=>$panelId]);
        $labels = defined("CODING_LANGUAGE_LABELS") ? CODING_LANGUAGE_LABELS : [];
        $this->jsonResponse(["ok"=>true,"label"=>$labels[$lang]??$lang]);
    }

    // ── AJAX: request session extension (interviewer → needs HR sign-off) ──
    public function request_extension(): void {
        $this->requireRole("interviewer");
        if (!$this->validateCsrf()) { $this->jsonResponse(["error"=>"csrf"],403); return; }
        $panelId = $this->getIntInput("panel_id");
        $minutes = min(60, max(5, $this->getIntInput("minutes", 10)));
        $reason  = trim($this->getInput("reason") ?? "Technical issues during session");
        $db      = Database::getInstance();

        // Store the pending request
        $db->prepare("
            INSERT INTO session_extension_requests (panel_id, requested_by, minutes, reason, status, created_at)
            VALUES (:pid, :uid, :m, :r, 'pending', NOW())
            ON DUPLICATE KEY UPDATE minutes=:m2, reason=:r2, status='pending', created_at=NOW()
        ")->execute([
            "pid"=>$panelId,"uid"=>$this->currentUser["id"],
            "m"=>$minutes,"r"=>$reason,"m2"=>$minutes,"r2"=>$reason
        ]);
        $reqId = (int)$db->lastInsertId();

        // Notify all HR Admins
        $panel = $db->prepare("SELECT ip.*, jr.title FROM interview_panels ip JOIN job_requisitions jr ON ip.job_id=jr.id WHERE ip.id=:id");
        $panel->execute(["id"=>$panelId]); $p = $panel->fetch();
        $approveLink = BASE_URL . "/index.php?page=interviewer/live&action=approve_extension&req_id=$reqId&decision=approve&panel_id=$panelId";
        $rejectLink  = BASE_URL . "/index.php?page=interviewer/live&action=approve_extension&req_id=$reqId&decision=reject&panel_id=$panelId";

        foreach ((new UserModel())->findByRole("hr_admin") as $hr) {
            EmailService::getInstance()->send($hr["email"],
                "Extension Request — Panel #{$panelId}",
                "<p><strong>" . htmlspecialchars($this->currentUser["name"]) . "</strong> has requested a <strong>{$minutes}-minute extension</strong> for Panel #{$panelId} ({$p["title"]}).</p>
                 <p><em>Reason:</em> " . htmlspecialchars($reason) . "</p>
                 <p style='margin-top:16px'>
                   <a href='{$approveLink}' style='background:#16a34a;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;margin-right:8px'>Approve</a>
                   <a href='{$rejectLink}' style='background:#dc2626;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none'>Reject</a>
                 </p>"
            );
        }
        $this->jsonResponse(["ok"=>true,"message"=>"Extension request sent to HR Admin. Awaiting approval."]);
    }

    // ── GET: HR Admin approves/rejects via email link ──────────────────
    public function approve_extension(): void {
        $this->requireRole(["hr_admin"]);
        $reqId    = $this->getIntInput("req_id");
        $panelId  = $this->getIntInput("panel_id");
        $decision = $this->getInput("decision"); // 'approve' or 'reject'
        $db       = Database::getInstance();

        $req = $db->prepare("SELECT * FROM session_extension_requests WHERE id=:id AND panel_id=:pid AND status='pending'");
        $req->execute(["id"=>$reqId,"pid"=>$panelId]);
        $r = $req->fetch();

        if (!$r) {
            $this->setFlash("error","Request not found or already processed.");
            $this->redirect("index.php?page=dashboard");
            return;
        }

        if ($decision === "approve") {
            $db->prepare("UPDATE interview_panels SET extended_by_minutes = extended_by_minutes + :m WHERE id=:id")
               ->execute(["m"=>$r["minutes"],"id"=>$panelId]);
            $db->prepare("UPDATE session_extension_requests SET status='approved', decided_by=:uid, decided_at=NOW() WHERE id=:id")
               ->execute(["uid"=>$this->currentUser["id"],"id"=>$reqId]);
            $status = "approved";
            $this->setFlash("success","Extension of {$r["minutes"]} minutes approved for Panel #{$panelId}.");
        } else {
            $db->prepare("UPDATE session_extension_requests SET status='rejected', decided_by=:uid, decided_at=NOW() WHERE id=:id")
               ->execute(["uid"=>$this->currentUser["id"],"id"=>$reqId]);
            $status = "rejected";
            $this->setFlash("error","Extension request rejected for Panel #{$panelId}.");
        }

        // Notify the requesting interviewer
        $interviewer = (new UserModel())->findById((int)$r["requested_by"]);
        if ($interviewer) {
            EmailService::getInstance()->sendTemplate($interviewer["email"],
                "Extension Request " . ucfirst($status),
                "interview_reminder",
                ["name"=>$interviewer["name"],"job_title"=>"Panel #{$panelId}","date"=>"Your extension request was **{$status}**" . ($status==="approved" ? " ({$r["minutes"]} min added)" : "")]
            );
        }
        AuditLogger::getInstance()->log((int)$this->currentUser["id"],"interview_panel",$panelId,$status."_extension",[],["req_id"=>$reqId]);
        $this->redirect("index.php?page=dashboard");
    }

    // ── AJAX: instant extend (HR Admin only, no approval needed) ──────
    public function extend(): void {
        $this->requireRole("hr_admin");
        if (!$this->validateCsrf()) { $this->jsonResponse(["error"=>"csrf"],403); return; }
        $panelId = $this->getIntInput("panel_id");
        $minutes = min(60, max(5, $this->getIntInput("minutes", 10)));
        Database::getInstance()
            ->prepare("UPDATE interview_panels SET extended_by_minutes = extended_by_minutes + :m WHERE id=:id")
            ->execute(["m"=>$minutes,"id"=>$panelId]);
        $this->jsonResponse(["ok"=>true,"added"=>$minutes]);

    }

    // ── AJAX: end session (interviewer only) ──────────────────────────
    public function end_session(): void {
        $this->requireRole("interviewer");
        if (!$this->validateCsrf()) { $this->jsonResponse(["error"=>"csrf"],403); return; }
        $panelId = $this->getIntInput("panel_id");
        Database::getInstance()
            ->prepare("UPDATE interview_panels SET status='completed' WHERE id=:id AND status IN ('active','scheduled')")
            ->execute(["id"=>$panelId]);
        AuditLogger::getInstance()->log((int)$this->currentUser["id"],"interview_panel",$panelId,"ended",[],[]);
        $this->jsonResponse(["ok"=>true]);
    }

    // ── AJAX: flag candidate on tab switch ────────────────────────────
    public function flag_candidate(): void {
        $panelId = $this->getIntInput("panel_id");
        $strikes = $this->getIntInput("strikes");
        if ($panelId < 1) { $this->jsonResponse(["ok"=>false]); return; }
        $db = Database::getInstance();
        // Get candidate_id from panel
        $stmt = $db->prepare("SELECT a.candidate_id, jr.title as job_title FROM interview_panels ip JOIN applications a ON ip.application_id=a.id JOIN job_requisitions jr ON ip.job_id=jr.id WHERE ip.id=:id");
        $stmt->execute(["id"=>$panelId]); $p = $stmt->fetch();
        if (!$p) { $this->jsonResponse(["ok"=>false]); return; }
        if ($strikes >= 3) {
            // Flag the panel
            $db->prepare("UPDATE interview_panels SET status='completed' WHERE id=:id")->execute(["id"=>$panelId]);
            // Notify HR Admins
            foreach ((new UserModel())->findByRole("hr_admin") as $hr) {
                EmailService::getInstance()->sendTemplate($hr["email"],
                    "Interview Auto-Terminated: Tab Switch Violations",
                    "assessment_violation",
                    ["candidate_name"=>"Candidate #".$p["candidate_id"], "assessment_title"=>$p["job_title"], "strikes"=>$strikes, "integrity_score"=>"0"]);
            }
        }
        $this->jsonResponse(["ok"=>true,"strikes"=>$strikes]);
    }

    // ── Legacy index() — redirect to join ────────────────────────────
    public function index(): void {
        $panelId = $this->getIntInput("panel_id") ?: $this->getIntInput("id");
        if ($panelId) {
            $this->redirect("index.php?page=interviewer/live&action=join&id=$panelId");
        } else {
            $this->redirect("index.php?page=interviewer/schedule");
        }
    }

    // ── candidate/interview route → same join action ──────────────────
    public function candidate_join(): void { $this->join(); }
}