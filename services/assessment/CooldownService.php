<?php
declare(strict_types=1);
/** #14 Assessment Cool-down Manager */
class CooldownException extends \DomainException {
    public int $daysRemaining;
    public function __construct(int $days) { $this->daysRemaining = $days; parent::__construct("Cooldown active. $days days remaining."); }
}

class CooldownService {
    public function enforce(int $assessmentId, int $candidateId): void {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT a.cooldown_months, MAX(cs.submitted_at) as last_submitted FROM assessments a LEFT JOIN candidate_sessions cs ON cs.assessment_id=a.id AND cs.candidate_id=:cid AND cs.status IN ('submitted','expired') WHERE a.id=:aid GROUP BY a.id");
        $stmt->execute(["cid"=>$candidateId,"aid"=>$assessmentId]);
        $row = $stmt->fetch();
        if (!$row) return;
        if (!$row["last_submitted"]) return;

        $cooldownEnd = strtotime("+" . $row["cooldown_months"] . " months", strtotime($row["last_submitted"]));
        if (time() < $cooldownEnd) {
            $daysRemaining = (int)ceil(($cooldownEnd - time()) / 86400);
            throw new CooldownException($daysRemaining);
        }
    }
}
