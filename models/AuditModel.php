<?php
declare(strict_types=1);

class AuditModel extends BaseModel {
    protected string $table = 'audit_log';
    
    public function getRecent(int $limit=50): array {
        $stmt=$this->db->prepare("SELECT al.*, u.name as actor_name FROM audit_log al LEFT JOIN users u ON al.actor_id=u.id ORDER BY al.created_at DESC LIMIT :lim");
        $stmt->bindValue(':lim',$limit,PDO::PARAM_INT); $stmt->execute(); return $stmt->fetchAll();
    }
    
    public function findByEntity(string $entityType, int $entityId): array {
        return $this->findWhere('entity_type=:et AND entity_id=:eid', ['et'=>$entityType,'eid'=>$entityId], 'created_at', 'DESC');
    }
    // Override delete to prevent deletion (append-only)
    public function delete(int $id): bool { throw new \RuntimeException('Audit log is append-only'); }
    
    public function update(int $id, array $data): bool { throw new \RuntimeException('Audit log is append-only'); }
}
