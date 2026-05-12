<?php
declare(strict_types=1);
/** #8 Proctored Environment Controller */
class ProctoringService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function logEvent(int $sessionId, string $type): void {
        $stmt = $this->db->prepare("INSERT INTO proctoring_events (session_id,event_type) VALUES(:sid,:t)");
        $stmt->execute(["sid"=>$sessionId,"t"=>$type]);
        $score = $this->getIntegrityScore($sessionId);
        $this->db->prepare("UPDATE candidate_sessions SET integrity_score=:s WHERE id=:id")->execute(["s"=>$score,"id"=>$sessionId]);
        if ($score < PROCTORING_FLAG_THRESHOLD) {
            $this->db->prepare("UPDATE candidate_sessions SET status='flagged' WHERE id=:id")->execute(["id"=>$sessionId]);
        }
    }

    public function getIntegrityScore(int $sessionId): float {
        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM proctoring_events WHERE session_id=:sid");
        $stmt->execute(["sid"=>$sessionId]);
        $count = (int)$stmt->fetch()["cnt"];
        return max(0, 100 - ($count * PROCTORING_PENALTY));
    }
}
