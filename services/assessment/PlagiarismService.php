<?php
declare(strict_types=1);
/** #12 Plagiarism Detection — Jaccard similarity */
class PlagiarismService {
    public function check(string $candidateAnswer, string $referenceAnswer): float {
        $tokensA = array_unique(array_filter(preg_split("/\W+/", strtolower($candidateAnswer))));
        $tokensB = array_unique(array_filter(preg_split("/\W+/", strtolower($referenceAnswer))));
        if (empty($tokensA) || empty($tokensB)) return 0.0;
        $intersection = count(array_intersect($tokensA, $tokensB));
        $union = count(array_unique(array_merge($tokensA, $tokensB)));
        return $union > 0 ? round($intersection / $union, 4) : 0.0;
    }

    public function checkAndFlag(int $answerId, string $candidateAnswer, string $referenceAnswer): float {
        $score = $this->check($candidateAnswer, $referenceAnswer);
        if ($score > 0.75) {
            Database::getInstance()->prepare("UPDATE candidate_answers SET is_plagiarized=1 WHERE id=:id")->execute(["id"=>$answerId]);
        }
        return $score;
    }
}
