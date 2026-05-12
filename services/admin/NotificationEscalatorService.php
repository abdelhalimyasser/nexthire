<?php
declare(strict_types=1);
/** #42 Automated Notification Escalator — Observer */
class NotificationEscalatorService {

    private PDO $db;
    
    private NotificationModel $nm;
    
    public function __construct() { $this->db = Database::getInstance(); $this->nm = new NotificationModel(); }

    public function checkPendingFeedback(): array {
        $escalated = [];
        // Panels completed more than 24h ago without feedback
        $stmt = $this->db->prepare("SELECT ip.id as panel_id, pm.user_id, ip.scheduled_at, TIMESTAMPDIFF(HOUR, ip.scheduled_at, NOW()) as hours_ago
            FROM interview_panels ip JOIN panel_members pm ON pm.panel_id=ip.id
            LEFT JOIN feedback_submissions fs ON fs.panel_id=ip.id AND fs.interviewer_id=pm.user_id AND fs.submitted_at IS NOT NULL
            WHERE ip.status='completed' AND fs.id IS NULL AND pm.role != 'shadow'
            HAVING hours_ago >= " . FEEDBACK_REMINDER_HOURS);
        $stmt->execute(); $pending = $stmt->fetchAll();

        foreach ($pending as $p) {
            $hours = (int)$p["hours_ago"];
            if ($hours >= FEEDBACK_ESCALATION_HOURS) {
                // Escalate to HR
                $hrAdmins = (new UserModel())->findByRole("hr_admin");
                foreach ($hrAdmins as $admin) {
                    $this->nm->createNotification((int)$admin["id"], "feedback_escalation", "Interviewer #{$p["user_id"]} has not submitted feedback for panel #{$p["panel_id"]} after {$hours}h", "interview_panel", (int)$p["panel_id"]);
                }
                $escalated[] = ["panel_id"=>$p["panel_id"],"user_id"=>$p["user_id"],"level"=>"escalated"];
            } else {
                $this->nm->createNotification((int)$p["user_id"], "feedback_reminder", "Please submit your feedback for panel #{$p["panel_id"]}", "interview_panel", (int)$p["panel_id"]);
                $escalated[] = ["panel_id"=>$p["panel_id"],"user_id"=>$p["user_id"],"level"=>"reminder"];
            }
        }
        AuditLogger::getInstance()->log(null,"notification_escalator",null,"check_run",[],["processed"=>count($escalated)]);
        return $escalated;
    }
}
