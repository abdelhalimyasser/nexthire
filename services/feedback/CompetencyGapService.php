<?php
declare(strict_types=1);
/** #27 Competency Gap Visualizer */
class CompetencyGapService {
    public function computeGap(int $applicationId): array {
        $appModel = new ApplicationModel(); $app = $appModel->getWithDetails($applicationId);
        if (!$app) return [];

        // Get candidate actual scores from feedback
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT fd.dimension, AVG(fd.score) as avg_score FROM feedback_dimensions fd JOIN feedback_submissions fs ON fd.submission_id=fs.id JOIN interview_panels ip ON fs.panel_id=ip.id WHERE ip.application_id=:aid AND fs.is_shadow=0 GROUP BY fd.dimension");
        $stmt->execute(["aid"=>$applicationId]);
        $actuals = [];
        foreach ($stmt->fetchAll() as $r) $actuals[$r["dimension"]] = round((float)$r["avg_score"], 2);

        // Ideal profile (configurable per job, using defaults)
        $ideal = ["coding"=>8.0,"system_design"=>7.5,"communication"=>7.0,"culture_fit"=>7.0];
        $gaps = [];
        foreach (FEEDBACK_DIMENSIONS as $dim) {
            $actual = $actuals[$dim] ?? 0;
            $idealVal = $ideal[$dim] ?? 7.0;
            $gaps[$dim] = ["actual"=>$actual,"ideal"=>$idealVal,"gap"=>round($idealVal-$actual,2)];
        }
        return $gaps;
    }
}
