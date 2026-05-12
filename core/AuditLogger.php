<?php
declare(strict_types=1);

/**
 * Immutable Audit Trail Logger (Singleton).
 * S — Single Responsibility: Only appends audit records.
 * Append-only: Never UPDATE or DELETE from audit_log.
 */

class AuditLogger
{
	private static ?AuditLogger $instance = null;

	private PDO $db;

	private function __construct()
	{
		$this->db = Database::getInstance();
	}

	private function __clone() {}

	public static function getInstance(): AuditLogger
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function log(
	    ?int $actorId,
	    string $entityType,
	    ?int $entityId,
	    string $action,
	    array $before = [],
	    array $after = []
	): void {
		$stmt = $this->db->prepare(
		    "INSERT INTO audit_log (actor_id, entity_type, entity_id, action, before_state_json, after_state_json, ip_address, created_at)
		    VALUES (:actor_id, :entity_type, :entity_id, :action, :before_json, :after_json, :ip, NOW())"
		);
		$stmt->execute([
		                   'actor_id'    => $actorId,
		                   'entity_type' => $entityType,
		                   'entity_id'   => $entityId,
		                   'action'      => $action,
		                   'before_json' => !empty($before) ? json_encode($before) : null,
		                   'after_json'  => !empty($after) ? json_encode($after) : null,
		                   'ip'          => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
		               ]);

		// Also publish event for other listeners
		EventBus::getInstance()->publish('AuditLogCreated', [
		                              'actor_id' => $actorId, 'entity_type' => $entityType,
		                              'entity_id' => $entityId, 'action' => $action,
		                          ]);
	}

	public function getRecent(int $limit = 50): array
	{
		$stmt = $this->db->prepare(
		    "SELECT al.*, u.name as actor_name FROM audit_log al
		    LEFT JOIN users u ON al.actor_id = u.id
		    ORDER BY al.created_at DESC LIMIT :lim"
		);
		$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll();
	}
}
