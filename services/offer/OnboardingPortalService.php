<?php
declare(strict_types=1);
/** #35 Pre-Onboarding Welcome Portal */
class OnboardingPortalService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function initChecklist(int $applicationId): void {
        $docs = ["tax_form","government_id","bank_details","emergency_contact","signed_nda"];
        foreach ($docs as $d) {
            $this->db->prepare("INSERT IGNORE INTO onboarding_checklist (application_id,document_type,status) VALUES(:aid,:dt,'pending')")
                ->execute(["aid"=>$applicationId,"dt"=>$d]);
        }
    }

    public function uploadDocument(int $applicationId, string $docType): void {
        $this->db->prepare("UPDATE onboarding_checklist SET status='uploaded', uploaded_at=NOW() WHERE application_id=:aid AND document_type=:dt")
            ->execute(["aid"=>$applicationId,"dt"=>$docType]);
    }

    public function verifyDocument(int $applicationId, string $docType): void {
        $this->db->prepare("UPDATE onboarding_checklist SET status='verified', verified_at=NOW() WHERE application_id=:aid AND document_type=:dt")
            ->execute(["aid"=>$applicationId,"dt"=>$docType]);
    }

    public function isReady(int $applicationId): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM onboarding_checklist WHERE application_id=:aid AND status!='verified'");
        $stmt->execute(["aid"=>$applicationId]);
        return (int)$stmt->fetch()["cnt"] === 0;
    }

    public function getChecklist(int $applicationId): array {
        $stmt = $this->db->prepare("SELECT * FROM onboarding_checklist WHERE application_id=:aid ORDER BY document_type");
        $stmt->execute(["aid"=>$applicationId]); return $stmt->fetchAll();
    }
}
