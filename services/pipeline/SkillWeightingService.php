<?php
declare(strict_types=1);
/**
 * #2 Dynamic Skill-Weighting Engine — Strategy pattern with ScoringStrategyInterface.
 * O: New strategies added without modifying existing ones.
 */
interface ScoringStrategyInterface {
    public function calculate(array $jobSkills, array $candidateSkills): float;
}

class WeightedAverageStrategy implements ScoringStrategyInterface {
    public function calculate(array $jobSkills, array $candidateSkills): float {
        if (empty($jobSkills)) return 0.0;
        $candidateSet = array_map("strtolower", $candidateSkills);
        $totalWeight = 0; $matchedWeight = 0;
        foreach ($jobSkills as $skill) {
            $w = (float)($skill["weight"] ?? 1);
            $totalWeight += $w;
            if (in_array(strtolower($skill["skill_name"]), $candidateSet, true)) $matchedWeight += $w;
        }
        return $totalWeight > 0 ? round(($matchedWeight / $totalWeight) * 100, 2) : 0.0;
    }
}

class NormalizedScoringStrategy implements ScoringStrategyInterface {
    public function calculate(array $jobSkills, array $candidateSkills): float {
        if (empty($jobSkills)) return 0.0;
        $candidateSet = array_map("strtolower", $candidateSkills);
        $matched = 0; $required = 0; $total = count($jobSkills);
        foreach ($jobSkills as $skill) {
            if (!empty($skill["is_required"])) $required++;
            if (in_array(strtolower($skill["skill_name"]), $candidateSet, true)) $matched++;
        }
        $base = $total > 0 ? ($matched / $total) * 100 : 0;
        return round(min($base, 100), 2);
    }
}

class SkillWeightingService {
    private ScoringStrategyInterface $strategy;
    private JobRequisitionModel $jobModel;
    private CandidateModel $candidateModel;

    public function __construct(?ScoringStrategyInterface $strategy = null) {
        $this->strategy = $strategy ?? new WeightedAverageStrategy();
        $this->jobModel = new JobRequisitionModel();
        $this->candidateModel = new CandidateModel();
    }

    public function calculateMatchScore(int $jobId, int $candidateId): float {
        $jobSkills = $this->jobModel->getSkills($jobId);
        $candidateSkills = $this->candidateModel->getSkills($candidateId);
        return $this->strategy->calculate($jobSkills, $candidateSkills);
    }
}
