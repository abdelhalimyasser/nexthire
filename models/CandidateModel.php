<?php
declare(strict_types=1);

class CandidateModel extends BaseModel
{
    protected string $table = 'users';

    public function findById(int $id): ?array
    {
        return $this->findOneWhere('id = :id AND role = :role', ['id' => $id, 'role' => 'candidate']);
    }

    public function findAll(string $orderBy = 'id', string $direction = 'DESC', int $limit = 100, int $offset = 0): array
    {
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE role='candidate' ORDER BY {$orderBy} {$direction} LIMIT :lim OFFSET :off");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getSkills(int $candidateId): array
    {
        $user = parent::findById($candidateId);
        if ($user && !empty($user['specializations'])) {
            return json_decode($user['specializations'], true) ?? [];
        }
        return [];
    }
}
