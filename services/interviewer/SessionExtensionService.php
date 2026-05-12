<?php
declare(strict_types=1);
/** #20 Session Extension Protocol — Chain of Responsibility */
class SessionExtensionService {
    private PDO $db;
    private AuditLogger $audit;
    public function __construct() { $this->db = Database::getInstance(); $this->audit = AuditLogger::getInstance(); }

    public function request(int $panelId, int $minutes, string $reason, int $requesterId): int {
        $stmt = $this->db->prepare("INSERT INTO session_extensions (panel_id,requested_by,minutes,reason) VALUES(:pid,:rb,:m,:r)");
        $stmt->execute(["pid"=>$panelId,"rb"=>$requesterId,"m"=>$minutes,"r"=>$reason]);
        $id = (int)$this->db->lastInsertId();
        $this->audit->log($requesterId,"session_extension",$id,"requested",[],["panel_id"=>$panelId,"minutes"=>$minutes]);
        return $id;
    }

    public function approve(int $requestId, int $adminId): bool {
        $stmt = $this->db->prepare("SELECT * FROM session_extensions WHERE id=:id AND status='pending'");
        $stmt->execute(["id"=>$requestId]); $ext = $stmt->fetch();
        if (!$ext) return false;

        $this->db->prepare("UPDATE session_extensions SET status='approved',reviewed_by=:rb,reviewed_at=NOW() WHERE id=:id")->execute(["rb"=>$adminId,"id"=>$requestId]);
        $this->db->prepare("UPDATE interview_panels SET extended_by_minutes=extended_by_minutes+:m WHERE id=:pid")->execute(["m"=>$ext["minutes"],"pid"=>$ext["panel_id"]]);
        $this->audit->log($adminId,"session_extension",$requestId,"approved",[],["minutes"=>$ext["minutes"]]);
        return true;
    }
}
