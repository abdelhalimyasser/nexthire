<?php
declare(strict_types=1);
/**
 * #4 Application Deduplication — Pluggable matchers with confidence scoring.
 */
class DuplicateResult {
    public bool $isDuplicate; public float $confidence; public ?int $originalId;
    public function __construct(bool $dup, float $conf, ?int $origId=null) {
        $this->isDuplicate=$dup; $this->confidence=$conf; $this->originalId=$origId;
    }
}

class DeduplicationService {
    private ApplicationModel $appModel;

    public function __construct() { $this->appModel = new ApplicationModel(); }

    public function detectAndHandle(int $applicationId): DuplicateResult {
        $app = $this->appModel->getWithDetails($applicationId);
        if (!$app) return new DuplicateResult(false, 0.0);

        $dupes = $this->appModel->findDuplicates($app["candidate_email"], (int)$app["job_id"], $applicationId);
        if (empty($dupes)) return new DuplicateResult(false, 0.0);

        $confidence = 0.9; // Same email + same job = very high confidence
        $originalId = (int)$dupes[0]["id"];

        if ($confidence > 0.85) {
            $this->appModel->update($applicationId, ["duplicate_of" => $originalId, "stage" => "rejected"]);
            return new DuplicateResult(true, $confidence, $originalId);
        }
        return new DuplicateResult(true, $confidence, $originalId);
    }
}
