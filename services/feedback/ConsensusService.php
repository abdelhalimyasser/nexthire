<?php
declare(strict_types=1);
/** #25 Consensus Meeting Automator — Observer */
class ConsensusService {
    public function checkConsensus(int $panelId): bool {
        $im = new InterviewModel(); $members = $im->getMembers($panelId);
        $fm = new FeedbackModel(); $submissions = $fm->findByPanel($panelId);

        $nonShadow = array_filter($members, fn($m) => $m["role"] !== "shadow");
        $submitted = array_filter($submissions, fn($s) => $s["submitted_at"] !== null);

        if (count($submitted) >= count($nonShadow) && count($nonShadow) > 0) {
            EventBus::getInstance()->publish("ConsensusReadyEvent", ["panel_id" => $panelId]);
            $nm = new NotificationModel();
            foreach ($nonShadow as $m) {
                $nm->createNotification((int)$m["user_id"], "consensus_ready", "All feedback submitted for panel #$panelId. Ready for consensus meeting.", "interview_panel", $panelId);
            }
            return true;
        }
        return false;
    }
}
