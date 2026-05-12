<?php
declare(strict_types=1);
/** #18 Live Coding Environment Sync — Observer pattern via polling */
class LiveCodingSyncService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function updateCode(int $panelId, string $code): void {
        $stmt = $this->db->prepare("INSERT INTO live_sessions (panel_id, current_code, last_updated_at) VALUES(:pid,:code,NOW()) ON DUPLICATE KEY UPDATE current_code=:code2, last_updated_at=NOW()");
        $stmt->execute(["pid"=>$panelId,"code"=>$code,"code2"=>$code]);
        EventBus::getInstance()->publish("CodeChangeEvent", ["panel_id"=>$panelId]);
    }

    public function getCode(int $panelId): string {
        $stmt = $this->db->prepare("SELECT current_code FROM live_sessions WHERE panel_id=:pid");
        $stmt->execute(["pid"=>$panelId]);
        $row = $stmt->fetch();
        return $row ? ($row["current_code"] ?? "") : "";
    }
}
