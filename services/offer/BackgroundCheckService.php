<?php
declare(strict_types=1);
/** #34 Background Check Integration */
class BackgroundCheckService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function trigger(int $applicationId): void {
        $ref = "BGC-" . $applicationId . "-" . time();
        $this->db->prepare("INSERT INTO background_checks (application_id,status,provider_ref) VALUES(:aid,'pending',:ref) ON DUPLICATE KEY UPDATE status='pending',provider_ref=:ref2")
            ->execute(["aid"=>$applicationId,"ref"=>$ref,"ref2"=>$ref]);
        AuditLogger::getInstance()->log(null,"background_check",$applicationId,"triggered",[],["provider_ref"=>$ref]);
    }

    public function pollStatus(int $applicationId): string {
        $stmt = $this->db->prepare("SELECT * FROM background_checks WHERE application_id=:aid"); $stmt->execute(["aid"=>$applicationId]); $bc = $stmt->fetch();
        if (!$bc) return "not_found";
        $count = (int)$bc["poll_count"] + 1;
        $this->db->prepare("UPDATE background_checks SET poll_count=:c, updated_at=NOW() WHERE id=:id")->execute(["c"=>$count,"id"=>$bc["id"]]);
        // Simulate: resolve after 3 polls, 90% pass rate
        if ($count >= 3) {
            $result = (rand(1,10) <= 9) ? "pass" : "fail";
            $this->db->prepare("UPDATE background_checks SET status=:s WHERE id=:id")->execute(["s"=>$result,"id"=>$bc["id"]]);
            return $result;
        }
        return "pending";
    }
}
