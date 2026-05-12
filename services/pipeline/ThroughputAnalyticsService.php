<?php
declare(strict_types=1);
/**
 * #6 Pipeline Throughput Analytics — calculates avg time per stage.
 */
class ThroughputAnalyticsService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function analyze(int $jobId = 0): array {
        $where = $jobId ? "WHERE p.application_id IN (SELECT id FROM applications WHERE job_id=:jid)" : "";
        $sql = "SELECT p.from_stage, p.to_stage, AVG(TIMESTAMPDIFF(HOUR, prev.changed_at, p.changed_at)) as avg_hours
                FROM pipeline_stages_log p
                LEFT JOIN pipeline_stages_log prev ON prev.application_id=p.application_id AND prev.to_stage=p.from_stage
                {$where} GROUP BY p.from_stage, p.to_stage";
        $stmt = $this->db->prepare($sql);
        $jobId ? $stmt->execute(["jid" => $jobId]) : $stmt->execute();
        $stages = $stmt->fetchAll();

        $maxHours = 0; $bottleneck = "applied";
        foreach ($stages as $s) {
            if ((float)$s["avg_hours"] > $maxHours) { $maxHours = (float)$s["avg_hours"]; $bottleneck = $s["from_stage"] ?? "applied"; }
        }
        $totalHours = array_sum(array_column($stages, "avg_hours"));
        return ["stages" => $stages, "avg_time_to_hire" => round($totalHours / 24, 1), "bottleneck" => $bottleneck];
    }
}
