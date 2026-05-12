<?php
declare(strict_types=1);
/** #24 Candidate Red-Flag Escalation — Observer */
class RedFlagService {
    private FeedbackModel $fm;
    private NotificationModel $nm;
    private AuditLogger $audit;

    public function __construct() { $this->fm = new FeedbackModel(); $this->nm = new NotificationModel(); $this->audit = AuditLogger::getInstance(); }

    public function raise(int $submissionId, string $description, string $severity): int {
        $flagId = $this->fm->addRedFlag($submissionId, $description, $severity);

        if ($severity === "critical" || $severity === "medium") {
            $hrAdmins = (new UserModel())->findByRole("hr_admin");
            foreach ($hrAdmins as $admin) {
                $this->nm->createNotification((int)$admin["id"], "red_flag", "Red flag ($severity): $description", "red_flag", $flagId);
            }
        }
        if ($severity === "critical") {
            $sub = $this->fm->findById($submissionId);
            if ($sub) {
                $appModel = new ApplicationModel();
                $panels = (new InterviewModel())->findById((int)$sub["panel_id"]);
                if ($panels) $appModel->update((int)$panels["application_id"], ["is_frozen" => 1]);
            }
        }
        $this->audit->log(null, "red_flag", $flagId, "raised", [], ["severity"=>$severity,"description"=>$description]);
        return $flagId;
    }
}
