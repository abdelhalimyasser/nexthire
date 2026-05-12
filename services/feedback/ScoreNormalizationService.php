<?php
declare(strict_types=1);
/** #23 Score Normalization Algorithm */
class ScoreNormalizationService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function normalize(float $rawScore, int $interviewerId): float {
        $stmt = $this->db->prepare("SELECT AVG(fd.score) as avg_score FROM feedback_dimensions fd JOIN feedback_submissions fs ON fd.submission_id=fs.id WHERE fs.interviewer_id=:iid");
        $stmt->execute(["iid"=>$interviewerId]);
        $interviewer_avg = (float)($stmt->fetch()["avg_score"] ?? $rawScore);

        $stmt2 = $this->db->prepare("SELECT AVG(score) as avg_score FROM feedback_dimensions");
        $stmt2->execute();
        $global_avg = (float)($stmt2->fetch()["avg_score"] ?? $rawScore);

        if ($interviewer_avg == 0 || $global_avg == 0) return $rawScore;
        $harshness = $interviewer_avg / $global_avg;
        return $harshness > 0 ? round($rawScore / $harshness, 2) : $rawScore;
    }
}
