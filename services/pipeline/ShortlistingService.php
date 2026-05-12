<?php
declare(strict_types=1);
/**
 * #5 AI-Ranked Shortlisting — Strategy: keyword density + experience weighting.
 */
class ShortlistingService {
    private ApplicationModel $appModel;
    private JobRequisitionModel $jobModel;
    private SkillWeightingService $skillService;

    public function __construct() {
        $this->appModel = new ApplicationModel();
        $this->jobModel = new JobRequisitionModel();
        $this->skillService = new SkillWeightingService();
    }

    public function generateShortlist(int $jobId): array {
        $job = $this->jobModel->findById($jobId);
        if (!$job) return [];
        $apps = $this->appModel->findByJob($jobId);
        $scored = [];

        foreach ($apps as $app) {
            if ($app["stage"] === "rejected") continue;
            $matchScore = $this->skillService->calculateMatchScore($jobId, (int)$app["candidate_id"]);
            $keywordScore = $this->computeKeywordDensity($app["resume_text"] ?? "", $job["description"]);
            $finalScore = ($matchScore * 0.6) + ($keywordScore * 0.4);
            $this->appModel->update((int)$app["id"], ["match_score" => $finalScore]);
            $scored[] = array_merge($app, ["computed_score" => $finalScore]);
        }

        usort($scored, fn($a, $b) => $b["computed_score"] <=> $a["computed_score"]);
        $topCount = max(1, (int)ceil(count($scored) * 0.1));
        return array_slice($scored, 0, $topCount);
    }

    private function computeKeywordDensity(string $resume, string $description): float {
        if (empty($resume) || empty($description)) return 0.0;
        $descWords = array_unique(array_filter(preg_split("/\W+/", strtolower($description))));
        $resumeWords = array_filter(preg_split("/\W+/", strtolower($resume)));
        if (empty($descWords)) return 0.0;
        $matched = count(array_intersect($resumeWords, $descWords));
        return min(100, round(($matched / count($descWords)) * 100, 2));
    }
}
