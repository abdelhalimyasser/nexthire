<?php
declare(strict_types=1);
/** #17 Automated Interview Briefing Generator — Factory */
class BriefingGeneratorService {
    public function generate(int $panelId): array {
        $im = new InterviewModel(); $panel = $im->findById($panelId);
        if (!$panel) throw new \DomainException("Panel not found");
        $appModel = new ApplicationModel(); $app = $appModel->getWithDetails((int)$panel["application_id"]);
        $members = $im->getMembers($panelId);
        $jobModel = new JobRequisitionModel(); $skills = $jobModel->getSkills((int)$panel["job_id"]);

        return [
            "panel_id" => $panelId, "scheduled_at" => $panel["scheduled_at"], "duration" => $panel["duration_minutes"],
            "candidate" => ["name" => $app["candidate_name"] ?? "N/A", "email" => $app["candidate_email"] ?? "", "match_score" => $app["match_score"] ?? 0, "current_stage" => $app["stage"] ?? ""],
            "job" => ["title" => $app["job_title"] ?? "", "department" => $app["department"] ?? "", "level" => $app["level"] ?? ""],
            "required_skills" => $skills, "panel_members" => $members,
            "evaluation_dimensions" => FEEDBACK_DIMENSIONS,
        ];
    }
}
