<?php
declare(strict_types=1);
/** #21 Interviewer Load Balancer */
class LoadBalancerService {
    public function assignInterviewer(int $jobId, string $requiredRole): array {
        $userModel = new UserModel();
        $interviewers = $userModel->getInterviewersWithLoad();

        foreach ($interviewers as $iv) {
            if ($requiredRole === "hr" && $iv["department"] === "HR") return $iv;
            if ($requiredRole === "technical" && $iv["department"] !== "HR") return $iv;
        }
        if (!empty($interviewers)) return $interviewers[0];
        throw new \DomainException("No available interviewer for role: $requiredRole");
    }
}
