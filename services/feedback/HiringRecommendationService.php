<?php
declare(strict_types=1);
/** #28 Hiring Recommendation State Machine */
class HiringRecommendationService {
    public function decide(int $applicationId): string {
        $aggregator = new FeedbackAggregatorService();
        $db = Database::getInstance();

        // Find panel for this application
        $stmt = $db->prepare("SELECT id FROM interview_panels WHERE application_id=:aid ORDER BY scheduled_at DESC LIMIT 1");
        $stmt->execute(["aid"=>$applicationId]);
        $panel = $stmt->fetch();
        if (!$panel) return "no_hire";

        $result = $aggregator->aggregate((int)$panel["id"]);
        $score = $result["overall_score"];

        if ($score >= HIRE_THRESHOLDS["strong_hire"]) $rec = "strong_hire";
        elseif ($score >= HIRE_THRESHOLDS["hire"]) $rec = "hire";
        elseif ($score >= HIRE_THRESHOLDS["no_hire"]) $rec = "no_hire";
        else $rec = "strong_no_hire";

        $stmt = $db->prepare("INSERT INTO hiring_recommendations (application_id,recommendation,final_score) VALUES(:aid,:rec,:sc) ON DUPLICATE KEY UPDATE recommendation=:rec2, final_score=:sc2, decided_at=NOW()");
        $stmt->execute(["aid"=>$applicationId,"rec"=>$rec,"sc"=>$score,"rec2"=>$rec,"sc2"=>$score]);

        AuditLogger::getInstance()->log(null,"hiring_recommendation",$applicationId,"decided",[],["recommendation"=>$rec,"score"=>$score]);
        return $rec;
    }
}
