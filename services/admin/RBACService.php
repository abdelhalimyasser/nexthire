<?php
declare(strict_types=1);
/** #36 RBAC Service */
class RBACService {
    public function can(string $role, string $permission): bool { return RBACMiddleware::can($role, $permission); }
    
    public function enforce(string $permission): void { RBACMiddleware::enforce($permission); }
    
    public function getUserPermissions(string $role): array { return ROLE_PERMISSIONS[$role] ?? []; }
}
