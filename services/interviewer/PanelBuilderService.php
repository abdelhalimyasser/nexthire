<?php
declare(strict_types=1);
/** #16 Multi-Representative Panel Builder */
class PanelValidationException extends \DomainException {}

class PanelBuilderService {
    private InterviewModel $im;
    public function __construct() { $this->im = new InterviewModel(); }

    public function buildPanel(int $jobId, int $applicationId): int {
        $lb = new LoadBalancerService();
        $technical = $lb->assignInterviewer($jobId, "technical");
        $hr = $lb->assignInterviewer($jobId, "hr");

        $panelId = $this->im->create(["job_id"=>$jobId,"application_id"=>$applicationId,"scheduled_at"=>date("Y-m-d H:i:s",strtotime("+3 days")),"duration_minutes"=>60,"status"=>"scheduled"]);
        $this->im->addMember($panelId, (int)$technical["id"], "technical");
        $this->im->addMember($panelId, (int)$hr["id"], "hr");
        return $panelId;
    }

    public function validatePanel(int $panelId): bool {
        $members = $this->im->getMembers($panelId);
        $roles = array_column($members, "role");
        $missing = [];
        if (!in_array("technical", $roles)) $missing[] = "technical";
        if (!in_array("hr", $roles)) $missing[] = "hr";
        $hasSenior = false;
        foreach ($members as $m) { if ($m["role"]==="technical" && ($m["seniority"]??"") === "senior") $hasSenior = true; }
        if (in_array("technical", $roles) && !$hasSenior) $missing[] = "senior technical member";
        if (!empty($missing)) throw new PanelValidationException("Missing: " . implode(", ", $missing));
        return true;
    }
}
