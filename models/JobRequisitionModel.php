<?php
declare(strict_types=1);

class JobRequisitionModel extends BaseModel
{
    protected string $table = 'job_requisitions';

    public function findWithCreator(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT j.*, u.name as creator_name FROM job_requisitions j JOIN users u ON j.created_by=u.id WHERE j.id=:id");
        $stmt->execute(['id' => $id]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public function findByStatus(string $status): array
    {
        return $this->findWhere('status = :s', ['s' => $status]);
    }

    public function findLive(): array
    {
        return $this->findWhere('status = :s', ['s' => 'live'], 'created_at', 'DESC');
    }

    public function getSkills(int $jobId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM job_skills WHERE job_id = :jid ORDER BY weight DESC");
        $stmt->execute(['jid' => $jobId]);
        return $stmt->fetchAll();
    }

    public function addSkill(int $jobId, string $name, float $weight, bool $required): int
    {
        $stmt = $this->db->prepare("INSERT INTO job_skills (job_id, skill_name, weight, is_required) VALUES (:jid,:name,:w,:r)");
        $stmt->execute(['jid' => $jobId, 'name' => $name, 'w' => $weight, 'r' => $required ? 1 : 0]);
        return (int)$this->db->lastInsertId();
    }

    public function countByStatus(): array
    {
        $stmt = $this->db->prepare("SELECT status, COUNT(*) as cnt FROM job_requisitions GROUP BY status");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
