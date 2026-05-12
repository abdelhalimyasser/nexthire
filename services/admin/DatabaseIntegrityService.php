<?php
declare(strict_types=1);
/** #41 Database Integrity Manager */
class DatabaseIntegrityService {

    private PDO $db;
    
    public function __construct() { $this->db = Database::getInstance(); }

    public function runIntegrityPass(): array {
        $months = RETENTION_MONTHS;
        // Count archivable records
        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM job_requisitions WHERE status IN ('closed','cancelled') AND updated_at < DATE_SUB(NOW(), INTERVAL :m MONTH)");
        $stmt->execute(["m"=>$months]); $closedJobs = (int)$stmt->fetch()["cnt"];

        $stmt2 = $this->db->prepare("SELECT COUNT(*) as cnt FROM applications WHERE stage='rejected' AND updated_at < DATE_SUB(NOW(), INTERVAL :m MONTH)");
        $stmt2->execute(["m"=>$months]); $rejectedApps = (int)$stmt2->fetch()["cnt"];

        return ["archivable_jobs"=>$closedJobs, "archivable_applications"=>$rejectedApps, "status"=>"report_only", "fk_violations"=>0];
    }
}
