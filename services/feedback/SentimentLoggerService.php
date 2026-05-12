<?php
declare(strict_types=1);
/** #26 Post-Interview Sentiment Logger */
class SentimentLoggerService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function log(int $candidateId, int $panelId, int $score, ?string $comment = null): void {
        $stmt = $this->db->prepare("INSERT INTO sentiment_logs (candidate_id,interview_panel_id,score,comment) VALUES(:cid,:pid,:s,:c)");
        $stmt->execute(["cid"=>$candidateId,"pid"=>$panelId,"s"=>max(1,min(5,$score)),"c"=>$comment]);
    }

    public function getReport(?int $departmentId = null): array {
        $sql = "SELECT AVG(sl.score) as avg_score, COUNT(*) as total FROM sentiment_logs sl";
        if ($departmentId) { $sql .= " JOIN interview_panels ip ON sl.interview_panel_id=ip.id JOIN job_requisitions j ON ip.job_id=j.id WHERE j.department=:dept"; }
        $stmt = $this->db->prepare($sql);
        $departmentId ? $stmt->execute(["dept"=>(string)$departmentId]) : $stmt->execute();
        return $stmt->fetch() ?: ["avg_score"=>0,"total"=>0];
    }
}
