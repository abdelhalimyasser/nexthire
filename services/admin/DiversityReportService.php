<?php
declare(strict_types=1);
/** #38 Diversity & Inclusion Audit Reporter */
class DiversityReportService {
    
    public function generateReport(int $jobId = 0): array {
        $db = Database::getInstance();
        $where = $jobId ? "WHERE a.job_id=:jid" : "";
        $sql = "SELECT u.diversity_gender as grp, 'gender' as dim, COUNT(*) as cnt FROM users u JOIN applications a ON u.id=a.candidate_id $where GROUP BY u.diversity_gender
                UNION ALL SELECT u.diversity_ethnicity, 'ethnicity', COUNT(*) FROM users u JOIN applications a ON u.id=a.candidate_id $where GROUP BY u.diversity_ethnicity";
        $stmt = $db->prepare($sql); $jobId ? $stmt->execute(["jid"=>$jobId]) : $stmt->execute();
        $raw = $stmt->fetchAll(); $report = ["gender"=>[],"ethnicity"=>[]]; $totals = ["gender"=>0,"ethnicity"=>0];
        foreach ($raw as $r) { if (!$r["grp"]) continue; $totals[$r["dim"]] += (int)$r["cnt"]; }
        foreach ($raw as $r) {
            if (!$r["grp"]) continue;
            if ((int)$r["cnt"] < K_ANONYMITY_THRESHOLD) continue; // k-anonymity
            $pct = $totals[$r["dim"]] > 0 ? round(((int)$r["cnt"] / $totals[$r["dim"]]) * 100, 1) : 0;
            $report[$r["dim"]][] = ["group"=>$r["grp"],"count"=>(int)$r["cnt"],"percentage"=>$pct];
        }
        return $report;
    }
}
