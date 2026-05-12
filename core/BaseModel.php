<?php
declare(strict_types=1);

/**
 * Abstract Base Model with generic CRUD helpers.
 * S — Single Responsibility: Only provides DB CRUD primitives.
 * L — Liskov: All child models are substitutable for BaseModel.
 * D — Dependency Inversion: Depends on PDO abstraction, not concrete driver.
 */

abstract class BaseModel
{
	protected PDO $db;
	protected string $table;
	protected string $primaryKey = 'id';

	public function __construct()
	{
		$this->db = Database::getInstance();
	}

	public function findById(int $id): ?array
	{
		$stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1");
		$stmt->execute(['id' => $id]);
		$result = $stmt->fetch();
		return $result ?: null;
	}

	public function findAll(string $orderBy = 'id', string $direction = 'DESC', int $limit = 100, int $offset = 0): array
	{
		$direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
		$stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY {$orderBy} {$direction} LIMIT :lim OFFSET :off");
		$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
		$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	public function create(array $data): int
	{
		$columns = implode(', ', array_keys($data));
		$placeholders = implode(', ', array_map(fn($k) => ':' . $k, array_keys($data)));
		$stmt = $this->db->prepare("INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})");
		$stmt->execute($data);
		return (int) $this->db->lastInsertId();
	}

	public function update(int $id, array $data): bool
	{
		$sets = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($data)));
		$data['_id'] = $id;
		$stmt = $this->db->prepare("UPDATE {$this->table} SET {$sets} WHERE {$this->primaryKey} = :_id");
		return $stmt->execute($data);
	}

	public function delete(int $id): bool
	{
		$stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");
		return $stmt->execute(['id' => $id]);
	}

	public function count(string $where = '1=1', array $params = []): int
	{
		$stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM {$this->table} WHERE {$where}");
		$stmt->execute($params);
		return (int) $stmt->fetch()['cnt'];
	}

	public function findWhere(string $where, array $params = [], string $orderBy = 'id', string $direction = 'DESC'): array
	{
		$direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
		$stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$where} ORDER BY {$orderBy} {$direction}");
		$stmt->execute($params);
		return $stmt->fetchAll();
	}

	public function findOneWhere(string $where, array $params = []): ?array
	{
		$stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$where} LIMIT 1");
		$stmt->execute($params);
		$result = $stmt->fetch();
		return $result ?: null;
	}
}
