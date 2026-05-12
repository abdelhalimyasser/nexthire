<?php
declare(strict_types=1);
/** #19 Interviewer Shadowing Logic */
class ShadowingService {
    public function isShadow(int $panelId, int $userId): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT role FROM panel_members WHERE panel_id=:pid AND user_id=:uid");
        $stmt->execute(["pid"=>$panelId,"uid"=>$userId]);
        $row = $stmt->fetch();
        return $row && $row["role"] === "shadow";
    }
}
