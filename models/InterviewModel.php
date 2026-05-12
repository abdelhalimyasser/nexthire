<?php
declare(strict_types=1);
class InterviewModel extends BaseModel {

    protected string $table = 'interview_panels';

    public function findByApplication(int $appId): array { return $this->findWhere('application_id=:aid', ['aid'=>$appId]); }
    
    public function findUpcoming(int $userId, string $role='interviewer'): array {
        if ($role==='interviewer') {
            $stmt=$this->db->prepare("SELECT ip.*, j.title as job_title, u.name as candidate_name FROM interview_panels ip JOIN panel_members pm ON pm.panel_id=ip.id JOIN job_requisitions j ON ip.job_id=j.id JOIN applications a ON ip.application_id=a.id JOIN users u ON a.candidate_id=u.id WHERE pm.user_id=:uid AND ip.status='scheduled' AND ip.scheduled_at>=NOW() ORDER BY ip.scheduled_at ASC");
        } else {
            $stmt=$this->db->prepare("SELECT ip.*, j.title as job_title FROM interview_panels ip JOIN applications a ON ip.application_id=a.id JOIN job_requisitions j ON ip.job_id=j.id WHERE a.candidate_id=:uid AND ip.status='scheduled' AND ip.scheduled_at>=NOW() ORDER BY ip.scheduled_at ASC");
        }
        $stmt->execute(['uid'=>$userId]); return $stmt->fetchAll();
    }
    
    public function getMembers(int $panelId): array {
        $stmt=$this->db->prepare("SELECT pm.*, u.name, u.email, u.seniority FROM panel_members pm JOIN users u ON pm.user_id=u.id WHERE pm.panel_id=:pid");
        $stmt->execute(['pid'=>$panelId]); return $stmt->fetchAll();
    }
    
    public function addMember(int $panelId, int $userId, string $role): void {
        $stmt=$this->db->prepare("INSERT INTO panel_members (panel_id,user_id,role) VALUES(:pid,:uid,:r) ON DUPLICATE KEY UPDATE role=:r2");
        $stmt->execute(['pid'=>$panelId,'uid'=>$userId,'r'=>$role,'r2'=>$role]);
    }
}
