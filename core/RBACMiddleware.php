<?php
declare(strict_types=1);

/**
 * Role-Based Access Control Middleware.
 * D — Dependency Inversion: Depends on ROLE_PERMISSIONS config, not concrete classes.
 * Decorator pattern: wraps controller actions with permission checks.
 */

class RBACMiddleware
{
    public static function can(string $role, string $permission): bool
    {
        $perms = ROLE_PERMISSIONS[$role] ?? [];
        return in_array($permission, $perms, true);
    }

    public static function enforce(string $permission): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=auth&action=login');
            exit;
        }
        $userModel = new UserModel();
        $user = $userModel->findById((int)$userId);
        if (!$user || !self::can($user['role'], $permission)) {
            http_response_code(403);
            include __DIR__ . '/../views/partials/403.php';
            exit;
        }
    }

    public static function enforceRole(string ...$allowedRoles): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=auth&action=login');
            exit;
        }
        $userModel = new UserModel();
        $user = $userModel->findById((int)$userId);
        if (!$user || !in_array($user['role'], $allowedRoles, true)) {
            http_response_code(403);
            include __DIR__ . '/../views/partials/403.php';
            exit;
        }
    }
}
