<?php
declare(strict_types=1);
/** #9 Randomized Question-Bank Generator — Factory pattern */
class QuestionBankService {
    private QuestionModel $qModel;
    private PDO $db;

    public function __construct() { $this->qModel = new QuestionModel(); $this->db = Database::getInstance(); }

    public function generateTest(int $assessmentId, int $candidateId): array {
        $stmt = $this->db->prepare("SELECT * FROM assessments WHERE id=:aid");
        $stmt->execute(["aid"=>$assessmentId]); $assessment = $stmt->fetch();
        if (!$assessment) throw new \DomainException("Assessment not found");

        // Get previously seen question IDs within cooldown
        $cooldown = (int)$assessment["cooldown_months"];
        $seenStmt = $this->db->prepare("SELECT ca.question_id FROM candidate_answers ca JOIN candidate_sessions cs ON ca.session_id=cs.id WHERE cs.candidate_id=:cid AND cs.assessment_id=:aid AND cs.started_at > DATE_SUB(NOW(), INTERVAL :months MONTH)");
        $seenStmt->execute(["cid"=>$candidateId,"aid"=>$assessmentId,"months"=>$cooldown]);
        $excludeIds = array_column($seenStmt->fetchAll(), "question_id");

        $easy = $this->qModel->findRandomByDifficulty("easy", (int)$assessment["num_easy"], $excludeIds, $assessmentId);
        $medium = $this->qModel->findRandomByDifficulty("medium", (int)$assessment["num_medium"], $excludeIds, $assessmentId);
        $hard = $this->qModel->findRandomByDifficulty("hard", (int)$assessment["num_hard"], $excludeIds, $assessmentId);

        $questions = array_merge($easy, $medium, $hard);
        shuffle($questions);
        return $questions;
    }
}
