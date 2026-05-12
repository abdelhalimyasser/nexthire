<?php
declare(strict_types=1);
/** #22 Multi-Dimensional Feedback Aggregator */
class FeedbackAggregatorService {
    private FeedbackModel $fm;
    private ShadowingService $shadow;

    public function __construct() { $this->fm = new FeedbackModel(); $this->shadow = new ShadowingService(); }

    public function aggregate(int $panelId): array {
        $submissions = $this->fm->findByPanel($panelId);
        $dimensionScores = []; $dimensionCounts = [];

        foreach ($submissions as $sub) {
            if ($sub["is_shadow"] || $this->shadow->isShadow($panelId, (int)$sub["interviewer_id"])) continue;
            $dims = $this->fm->getDimensions((int)$sub["id"]);
            foreach ($dims as $d) {
                $dim = $d["dimension"];
                $dimensionScores[$dim] = ($dimensionScores[$dim] ?? 0) + (float)$d["score"];
                $dimensionCounts[$dim] = ($dimensionCounts[$dim] ?? 0) + 1;
            }
        }

        $averages = []; $weightedTotal = 0; $totalWeight = 0;
        foreach (FEEDBACK_DIMENSIONS as $dim) {
            $avg = ($dimensionCounts[$dim] ?? 0) > 0 ? $dimensionScores[$dim] / $dimensionCounts[$dim] : 0;
            $averages[$dim] = round($avg, 2);
            $w = DIMENSION_WEIGHTS[$dim] ?? 0.25;
            $weightedTotal += $avg * $w;
            $totalWeight += $w;
        }
        $overall = $totalWeight > 0 ? round($weightedTotal / $totalWeight, 2) : 0;
        return ["dimensions" => $averages, "overall_score" => $overall, "submissions_count" => count($submissions)];
    }
}
