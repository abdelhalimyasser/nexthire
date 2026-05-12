<?php
declare(strict_types=1);
/** #37 Data Retention & Right to be Forgotten */
class DataRetentionService {
    
    public function runRetentionPass(): array {
        $db = Database::getInstance(); $months = RETENTION_MONTHS;
        $stmt = $db->prepare("SELECT DISTINCT a.candidate_id FROM applications a WHERE a.stage='rejected' AND a.updated_at < DATE_SUB(NOW(), INTERVAL :m MONTH) AND a.candidate_id NOT IN (SELECT candidate_id FROM applications WHERE stage NOT IN ('rejected'))");
        $stmt->execute(["m"=>$months]); $candidates = $stmt->fetchAll(); $anonymized = 0;
        foreach ($candidates as $c) { $this->forgetCandidate((int)$c["candidate_id"]); $anonymized++; }
        return ["scanned"=>count($candidates),"anonymized"=>$anonymized];
    }

    public function forgetCandidate(int $candidateId): void {
        $userModel = new UserModel(); $before = $userModel->findById($candidateId);
        if (!$before) return;
        AuditLogger::getInstance()->log(null,"user",$candidateId,"anonymize",["name"=>$before["name"]],["name"=>"ANONYMIZED"]);
        $userModel->anonymize($candidateId);
    }
}
