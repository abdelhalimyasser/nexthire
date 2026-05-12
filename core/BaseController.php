<?php
declare(strict_types=1);

/**
 * Abstract Base Controller.
 * S — Single Responsibility: HTTP handling, session/CSRF, view rendering.
 */

abstract class BaseController
{
	protected ?array $currentUser = null;

	public function __construct()
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		if (isset($_SESSION['user_id'])) {
			$userModel = new UserModel();
			$this->currentUser = $userModel->findById((int)$_SESSION['user_id']);
		}
	}

	protected function requireAuth(): void
	{
		if (!$this->currentUser) {
			header('Location: index.php?page=auth&action=login');
			exit;
		}
	}

	protected function requireRole(string|array ...$roles): void
	{
		$this->requireAuth();
		$flat = [];
		foreach ($roles as $r) {
			if (is_array($r)) foreach ($r as $v) $flat[]=$v;
			else $flat[]=$r;
		}
		if (!in_array($this->currentUser['role'], $flat, true)) {
			http_response_code(403);
			$this->render('partials/403');
			exit;
		}
	}

	protected function isAjax(): bool
	{
		return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
		|| (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'],'application/json'));
	}

	protected function requirePermission(string $permission): void
	{
		$this->requireAuth();
		$role = $this->currentUser['role'];
		$perms = ROLE_PERMISSIONS[$role] ?? [];
		if (!in_array($permission, $perms, true)) {
			http_response_code(403);
			$this->render('partials/403');
			exit;
		}
	}

	protected function validateCsrf(): bool
	{
		$token = $_POST[CSRF_TOKEN_NAME] ?? '';
		return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
	}

	protected function generateCsrf(): string
	{
		if (empty($_SESSION[CSRF_TOKEN_NAME])) {
			$_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
		}
		return $_SESSION[CSRF_TOKEN_NAME];
	}

	protected function csrfField(): string
	{
		return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($this->generateCsrf()) . '">';
	}

	protected function redirect(string $url): void
	{
		header('Location: ' . $url);
		exit;
	}

	protected function jsonResponse(array $data, int $code = 200): void
	{
		http_response_code($code);
		header('Content-Type: application/json');
		echo json_encode($data);
		exit;
	}

	protected function getPostData(): array
	{
		return $_POST;
	}

	protected function getInput(string $key, string $default = ''): string
	{
		return htmlspecialchars(trim($_GET[$key] ?? $_POST[$key] ?? $default), ENT_QUOTES, 'UTF-8');
	}

	protected function getIntInput(string $key, int $default = 0): int
	{
		return (int)($_GET[$key] ?? $_POST[$key] ?? $default);
	}

	protected function setFlash(string $type, string $message): void
	{
		$_SESSION['flash'] = ['type' => $type, 'message' => $message];
	}

	protected function getFlash(): ?array
	{
		$flash = $_SESSION['flash'] ?? null;
		unset($_SESSION['flash']);
		return $flash;
	}

	protected function render(string $view, array $data = []): void
	{
		$data['currentUser'] = $this->currentUser;
		$data['csrf'] = $this->csrfField();
		$data['flash'] = $this->getFlash();
		extract($data);
		$viewFile = __DIR__ . '/../views/' . $view . '.php';
		if (file_exists($viewFile)) {
			include $viewFile;
		}
	}

	protected function renderLayout(string $content, array $data = []): void
	{
		$data['currentUser'] = $this->currentUser;
		$data['csrf'] = $this->csrfField();
		$data['flash'] = $this->getFlash();
		$data['content'] = $content;
		extract($data);
		include __DIR__ . '/../views/layouts/main.php';
	}
}
