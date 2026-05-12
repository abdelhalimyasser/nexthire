<?php declare(strict_types=1);
class SchedulingController extends BaseController {
    public function index(): void {
        $this->requireRole("interviewer","hr_admin","shadow");
        $uid  = (int)$this->currentUser["id"];
        $role = $this->currentUser["role"];
        $db   = Database::getInstance();

        if ($role === "hr_admin") {
            $stmt = $db->prepare("
                SELECT ip.*, jr.title as job_title, u.name as candidate_name
                FROM interview_panels ip
                JOIN job_requisitions jr ON ip.job_id = jr.id
                JOIN applications a ON ip.application_id = a.id
                JOIN users u ON a.candidate_id = u.id
                WHERE ip.status IN ('scheduled','active')
                ORDER BY ip.scheduled_at ASC
            ");
            $stmt->execute();
        } else {
            $stmt = $db->prepare("
                SELECT ip.*, jr.title as job_title, u.name as candidate_name
                FROM interview_panels ip
                JOIN job_requisitions jr ON ip.job_id = jr.id
                JOIN applications a ON ip.application_id = a.id
                JOIN users u ON a.candidate_id = u.id
                JOIN panel_members pm ON pm.panel_id = ip.id
                WHERE pm.user_id = :uid AND ip.status IN ('scheduled','active')
                ORDER BY ip.scheduled_at ASC
            ");
            $stmt->execute(["uid" => $uid]);
        }
        $upcoming = $stmt->fetchAll();

        $slots = [];
        if ($role !== "hr_admin") {
            $slotStmt = $db->prepare("SELECT * FROM interviewer_slots WHERE user_id=:uid AND date>=CURDATE() ORDER BY date,start_time");
            $slotStmt->execute(["uid" => $uid]);
            $slots = $slotStmt->fetchAll();
        }

        $pageTitle = "Interview Schedule";
        ob_start();
        ?>
        <h1 class="text-2xl font-bold mb-6">Interview Schedule</h1>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <div class="bg-white rounded-xl border p-6">
                <h3 class="font-semibold mb-4">Upcoming Interviews <span class="text-xs text-slate-400">(<?= count($upcoming) ?>)</span></h3>
                <?php foreach ($upcoming as $u): ?>
                <div class="p-4 bg-slate-50 rounded-lg mb-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium"><?= htmlspecialchars($u["job_title"] ?? "") ?></p>
                        <p class="text-sm text-slate-500"><?= $u["scheduled_at"] ?> &middot; <?= $u["duration_minutes"] ?>min</p>
                        <p class="text-xs text-slate-400">Candidate: <?= htmlspecialchars($u["candidate_name"] ?? "") ?></p>
                    </div>
                    <a href="index.php?page=interviewer/live&action=join&id=<?= $u["id"] ?>"
                       class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition font-medium">
                        Join
                    </a>
                </div>
                <?php endforeach; ?>
                <?php if (empty($upcoming)): ?>
                <p class="text-slate-500 text-center py-6">No upcoming interviews.</p>
                <?php endif; ?>
            </div>

            <?php if ($role !== "hr_admin"): ?>
            <div class="bg-white rounded-xl border p-6">
                <h3 class="font-semibold mb-4">My Availability</h3>
                <form method="POST" action="index.php?page=interviewer/schedule&action=add_slot" class="flex gap-2 mb-4">
                    <?= $this->csrfField() ?>
                    <input name="date" type="date" class="px-3 py-2 border rounded-lg text-sm" required>
                    <input name="start_time" type="time" class="px-3 py-2 border rounded-lg text-sm" required>
                    <input name="end_time" type="time" class="px-3 py-2 border rounded-lg text-sm" required>
                    <button class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-lg">Add</button>
                </form>
                <div class="space-y-2">
                    <?php foreach ($slots as $s): ?>
                    <div class="flex justify-between items-center p-3 <?= $s["is_booked"] ? "bg-red-50" : "bg-emerald-50" ?> rounded-lg">
                        <span class="text-sm font-medium"><?= $s["date"] ?></span>
                        <span class="text-sm"><?= $s["start_time"] ?> - <?= $s["end_time"] ?></span>
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $s["is_booked"] ? "bg-red-200 text-red-700" : "bg-emerald-200 text-emerald-700" ?>">
                            <?= $s["is_booked"] ? "Booked" : "Free" ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <?php
        $content = ob_get_clean();
        $this->renderLayout($content, compact("pageTitle"));
    }

    public function add_slot(): void {
        $this->requireRole("interviewer","hr_admin","shadow");
        if ($this->validateCsrf()) {
            $db = Database::getInstance();
            $db->prepare("INSERT INTO interviewer_slots (user_id,date,start_time,end_time) VALUES(:uid,:d,:st,:et)")
               ->execute(["uid"=>$this->currentUser["id"],"d"=>$_POST["date"],"st"=>$_POST["start_time"],"et"=>$_POST["end_time"]]);
            $this->setFlash("success", "Slot added");
        }
        $this->redirect("index.php?page=interviewer/schedule");
    }
}