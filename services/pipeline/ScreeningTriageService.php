<?php
declare(strict_types=1);
/**
 * #1 Automated Screening Triage — Uses StateMachine for stage transitions.
 * S: Only handles stage transitions. O: New stages added via config.
 */
class ScreeningTriageService {
    private ApplicationModel $appModel;
    private EventBus $eventBus;
    private AuditLogger $audit;

    public function __construct() {
        $this->appModel = new ApplicationModel();
        $this->eventBus = EventBus::getInstance();
        $this->audit = AuditLogger::getInstance();
    }

    public function transition(int $applicationId, string $toStage, int $actorId, ?string $reason = null): bool {
        $app = $this->appModel->findById($applicationId);
        if (!$app) throw new \DomainException("Application not found");
        if ($app["is_frozen"]) throw new \DomainException("Application is frozen due to red flag");

        $sm = new StateMachine($app["stage"], STAGE_TRANSITIONS);
        $fromStage = $sm->transitionTo($toStage);

        $this->appModel->update($applicationId, ["stage" => $toStage]);
        $this->appModel->logStageChange($applicationId, $fromStage, $toStage, $actorId, $reason);
        $this->audit->log($actorId, "application", $applicationId, "stage_transition", ["stage" => $fromStage], ["stage" => $toStage]);
        $this->eventBus->publish("StageTransition", ["application_id" => $applicationId, "from" => $fromStage, "to" => $toStage, "actor_id" => $actorId]);

        // Auto-create interview panel when transitioning to 'interview' stage
        if ($toStage === 'interview') {
            $this->createInterviewPanel($applicationId, $actorId);
        }

        return true;
    }

    /**
     * Auto-create an interview panel when application reaches interview stage.
     * Schedules 2 business days out with 60-minute default duration.
     * Adds the triggering HR admin as a panel member with 'hr' role.
     */
    private function createInterviewPanel(int $applicationId, int $actorId): void {
        $db = Database::getInstance();

        // Check if a panel already exists for this application
        $existing = $db->prepare("SELECT id FROM interview_panels WHERE application_id = :aid LIMIT 1");
        $existing->execute(['aid' => $applicationId]);
        if ($existing->fetch()) {
            return; // Panel already exists, don't duplicate
        }

        // Get application details
        $app = $this->appModel->findById($applicationId);
        if (!$app) return;

        // Generate secure candidate token
        $candidateToken = bin2hex(random_bytes(32));

        // Schedule 2 days from now at 10:00 AM UTC
        $scheduledAt = date('Y-m-d 10:00:00', strtotime('+2 days'));

        // Create the panel
        $db->prepare("
            INSERT INTO interview_panels (job_id, application_id, scheduled_at, timezone, duration_minutes, status, candidate_token, coding_language)
            VALUES (:jid, :aid, :sat, 'UTC', 60, 'scheduled', :tok, 'javascript')
        ")->execute([
            'jid' => $app['job_id'],
            'aid' => $applicationId,
            'sat' => $scheduledAt,
            'tok' => $candidateToken,
        ]);
        $panelId = (int)$db->lastInsertId();

        // Add the HR admin who triggered the transition as an 'hr' panel member
        $actor = (new UserModel())->findById($actorId);
        if ($actor && $actor['role'] === 'hr_admin') {
            $db->prepare("INSERT INTO panel_members (panel_id, user_id, role) VALUES (:pid, :uid, 'hr')")
               ->execute(['pid' => $panelId, 'uid' => $actorId]);
        }

        // Send notification to candidate
        $candidate = (new UserModel())->findById((int)$app['candidate_id']);
        if ($candidate) {
            $db->prepare("INSERT INTO notifications (user_id, type, message) VALUES (:uid, 'interview', :msg)")
               ->execute([
                   'uid' => $candidate['id'],
                   'msg' => 'Your interview has been scheduled for ' . $scheduledAt . '. Check your dashboard for details.',
               ]);
        }

        $this->audit->log($actorId, 'interview_panel', $panelId, 'auto_created', [], ['application_id' => $applicationId]);
    }

    public function getAllowedTransitions(int $applicationId): array {
        $app = $this->appModel->findById($applicationId);
        if (!$app) return [];
        $sm = new StateMachine($app["stage"], STAGE_TRANSITIONS);
        return $sm->getAllowedTransitions();
    }
}
