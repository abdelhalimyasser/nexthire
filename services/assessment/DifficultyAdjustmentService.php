<?php
declare(strict_types=1);
/** #13 Dynamic Difficulty Adjustment */
class DifficultyAdjustmentService {
    public function suggest(int $candidateId, int $assessmentId): string {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT AVG(ca.score) as avg_score FROM candidate_answers ca JOIN candidate_sessions cs ON ca.session_id=cs.id WHERE cs.candidate_id=:cid AND cs.assessment_id=:aid AND ca.score IS NOT NULL");
        $stmt->execute(["cid"=>$candidateId,"aid"=>$assessmentId]);
        $row = $stmt->fetch();
        $avg = $row ? (float)($row["avg_score"] ?? 50) : 50;

        if ($avg < 40) $tier = "easy";
        elseif ($avg <= 70) $tier = "medium";
        else $tier = "hard";

        AuditLogger::getInstance()->log(null,"difficulty_adjustment",null,"suggest",["candidate"=>$candidateId],["tier"=>$tier,"avg"=>$avg]);
        return $tier;
    }
}
