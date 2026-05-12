<?php
declare(strict_types=1);
/** #39 System Audit Trail */
class AuditTrailService {

    public function log(int $actorId, string $entity, int $entityId, string $action, array $before = [], array $after = []): void {
        AuditLogger::getInstance()->log($actorId, $entity, $entityId, $action, $before, $after);
    }
    public function getRecent(int $limit = 50): array { return AuditLogger::getInstance()->getRecent($limit); }
    
    public function getByEntity(string $type, int $id): array { return (new AuditModel())->findByEntity($type, $id); }
}
