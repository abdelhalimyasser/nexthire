<?php
declare(strict_types=1);
/** #10 Timed-Session Heartbeat */
class SessionHeartbeatService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function check(int $sessionId): array {
        $stmt = $this->db->prepare("SELECT cs.*, a.total_time_minutes FROM candidate_sessions cs JOIN assessments a ON cs.assessment_id=a.id WHERE cs.id=:sid");
        $stmt->execute(["sid"=>$sessionId]); $session = $stmt->fetch();
        if (!$session) return ["status"=>"not_found"];
        if ($session["status"] !== "active") return ["status"=>$session["status"],"remaining"=>0];

        $started = strtotime($session["started_at"]);
        $duration = (int)$session["total_time_minutes"] * 60;
        $elapsed = time() - $started;
        $remaining = max(0, $duration - $elapsed);

        if ($remaining <= 0) { $this->autoSubmit($sessionId); return ["status"=>"expired","remaining"=>0]; }
        return ["status"=>"active","remaining"=>$remaining,"elapsed"=>$elapsed];
    }

    public function autoSubmit(int $sessionId): void {
        $this->db->prepare("UPDATE candidate_sessions SET status='submitted', submitted_at=NOW() WHERE id=:id AND status='active'")
            ->execute(["id"=>$sessionId]);
        AuditLogger::getInstance()->log(null,"candidate_session",$sessionId,"auto_submit",[],["reason"=>"time_expired"]);
    }
}
