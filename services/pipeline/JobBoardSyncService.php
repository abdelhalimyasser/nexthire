<?php
declare(strict_types=1);
/**
 * #7 External Job-Board Sync — Adapter pattern.
 */
interface JobBoardAdapterInterface {
    public function sync(array $job): array;
    public function getPlatform(): string;
}

class LinkedInAdapter implements JobBoardAdapterInterface {
    public function getPlatform(): string { return "linkedin"; }
    public function sync(array $job): array {
        return ["platform" => "linkedin", "status" => "success", "external_id" => "LI-" . $job["id"] . "-" . time()];
    }
}
class IndeedAdapter implements JobBoardAdapterInterface {
    public function getPlatform(): string { return "indeed"; }
    public function sync(array $job): array {
        return ["platform" => "indeed", "status" => "success", "external_id" => "IND-" . $job["id"] . "-" . time()];
    }
}
class GlassdoorAdapter implements JobBoardAdapterInterface {
    public function getPlatform(): string { return "glassdoor"; }
    public function sync(array $job): array {
        return ["platform" => "glassdoor", "status" => "success", "external_id" => "GD-" . $job["id"] . "-" . time()];
    }
}

class JobBoardSyncService {
    /** @var JobBoardAdapterInterface[] */
    private array $adapters;
    private PDO $db;
    private AuditLogger $audit;

    public function __construct() {
        $this->adapters = [new LinkedInAdapter(), new IndeedAdapter(), new GlassdoorAdapter()];
        $this->db = Database::getInstance();
        $this->audit = AuditLogger::getInstance();
    }

    public function syncToAll(int $jobId): array {
        $jobModel = new JobRequisitionModel();
        $job = $jobModel->findById($jobId);
        if (!$job) throw new \DomainException("Job not found");
        $results = [];
        foreach ($this->adapters as $adapter) {
            $result = $adapter->sync($job);
            $stmt = $this->db->prepare("INSERT INTO job_board_syncs (job_id,platform,external_id,status) VALUES(:jid,:p,:eid,:s)");
            $stmt->execute(["jid" => $jobId, "p" => $result["platform"], "eid" => $result["external_id"], "s" => $result["status"]]);
            $results[] = $result;
        }
        $this->audit->log(null, "job_board_sync", $jobId, "sync_all", [], ["results" => $results]);
        return $results;
    }
}
