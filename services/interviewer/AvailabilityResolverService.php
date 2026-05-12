<?php
declare(strict_types=1);
/** #15 Interviewer Availability Conflict Resolver */
class AvailabilityResolverService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function findSlots(int $panelId, array $candidateAvailability, string $timezone = "UTC"): array {
        $im = new InterviewModel(); $members = $im->getMembers($panelId);
        $userIds = array_column($members, "user_id");
        if (empty($userIds)) return [];

        $placeholders = implode(",", array_fill(0, count($userIds), "?"));
        $stmt = $this->db->prepare("SELECT * FROM interviewer_slots WHERE user_id IN ($placeholders) AND is_booked=0 AND date >= CURDATE() ORDER BY date, start_time");
        $stmt->execute($userIds);
        $allSlots = $stmt->fetchAll();

        // Group by date+time and find overlaps
        $grouped = []; $suggestions = [];
        foreach ($allSlots as $slot) {
            $key = $slot["date"] . "_" . $slot["start_time"];
            $grouped[$key][] = $slot;
        }
        foreach ($grouped as $key => $slots) {
            $availableCount = count($slots);
            if ($availableCount >= count($userIds) * 0.5) {
                $suggestions[] = ["date" => $slots[0]["date"], "start_time" => $slots[0]["start_time"], "end_time" => $slots[0]["end_time"], "available_count" => $availableCount, "total_needed" => count($userIds), "timezone" => $timezone];
            }
        }
        usort($suggestions, fn($a,$b) => $b["available_count"] <=> $a["available_count"]);
        return $suggestions;
    }
}
