<?php

declare(strict_types=1);

class OfferModel extends BaseModel {

    protected string $table = 'offers';

    public function findByApplication(int $appId): ?array { 
        return $this->findOneWhere('application_id=:aid', ['aid'=>$appId]); 
    }
    public function getNegotiations(int $offerId): array {
        $stmt=$this->db->prepare("SELECT * FROM offer_negotiations WHERE offer_id=:oid ORDER BY revision_number ASC");
        $stmt->execute(['oid'=>$offerId]); return $stmt->fetchAll();
    }
    public function addNegotiation(int $offerId, int $revision, float $salary, string $proposedBy, ?string $notes=null): int {
        $stmt=$this->db->prepare("INSERT INTO offer_negotiations (offer_id,revision_number,proposed_salary,proposed_by,notes) VALUES(:oid,:r,:s,:pb,:n)");
        $stmt->execute(['oid'=>$offerId,'r'=>$revision,'s'=>$salary,'pb'=>$proposedBy,'n'=>$notes]);
        return (int)$this->db->lastInsertId();
    }
    public function findExpired(): array { return $this->findWhere("status='sent' AND expires_at < NOW()"); }
    
    public function getBackgroundCheck(int $appId): ?array {
        $stmt=$this->db->prepare("SELECT * FROM background_checks WHERE application_id=:aid");
        $stmt->execute(['aid'=>$appId]); $r=$stmt->fetch(); return $r?:null;
    }
    public function getOnboardingChecklist(int $appId): array {
        $stmt=$this->db->prepare("SELECT * FROM onboarding_checklist WHERE application_id=:aid ORDER BY document_type");
        $stmt->execute(['aid'=>$appId]); return $stmt->fetchAll();
    }
}
