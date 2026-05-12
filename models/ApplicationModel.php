<?php
declare(strict_types=1);

class ApplicationModel extends BaseModel
{
	protected string $table = 'applications';

	public function findByJob(int $jobId): array
	{
		$stmt = $this->db->prepare(
		    "SELECT a.*, u.name as candidate_name, u.email as candidate_email
		    FROM applications a JOIN users u ON a.candidate_id=u.id
		    WHERE a.job_id=:jid ORDER BY a.applied_at DESC"
		);
		$stmt->execute(['jid' => $jobId]);
		return $stmt->fetchAll();
	}

	public function findByCandidate(int $candidateId): array
	{
		$stmt = $this->db->prepare(
		    "SELECT a.*, j.title as job_title, j.department
		    FROM applications a JOIN job_requisitions j ON a.job_id=j.id
		    WHERE a.candidate_id=:cid ORDER BY a.applied_at DESC"
		);
		$stmt->execute(['cid' => $candidateId]);
		return $stmt->fetchAll();
	}

	public function findByStage(string $stage): array
	{
		return $this->findWhere('stage=:s', ['s' => $stage]);
	}

	public function countByStage(int $jobId = 0): array
	{
		$where = $jobId ? "WHERE job_id=:jid" : "";
		$stmt = $this->db->prepare("SELECT stage, COUNT(*) as cnt FROM applications {$where} GROUP BY stage");
		$jobId ? $stmt->execute(['jid' => $jobId]) : $stmt->execute();
		return $stmt->fetchAll();
	}

	public function findDuplicates(string $email, int $jobId, int $excludeId = 0): array
	{
		$stmt = $this->db->prepare(
		    "SELECT a.* FROM applications a JOIN users u ON a.candidate_id=u.id
		    WHERE u.email=:email AND a.job_id=:jid AND a.id!=:eid"
		);
		$stmt->execute(['email' => $email, 'jid' => $jobId, 'eid' => $excludeId]);
		return $stmt->fetchAll();
	}

	public function getWithDetails(int $id): ?array
	{
		$stmt = $this->db->prepare(
		    "SELECT a.*, u.name as candidate_name, u.email as candidate_email,
		    j.title as job_title, j.department, j.level, j.location_tier
		    FROM applications a
		    JOIN users u ON a.candidate_id=u.id
		    JOIN job_requisitions j ON a.job_id=j.id
		    WHERE a.id=:id"
		);
		$stmt->execute(['id' => $id]);
		$r = $stmt->fetch();
		return $r ?: null;
	}

	public function getTopByMatchScore(int $jobId, int $limit = 5): array
	{
		$stmt = $this->db->prepare(
		    "SELECT a.*, u.name as candidate_name FROM applications a
		    JOIN users u ON a.candidate_id=u.id
		    WHERE a.job_id=:jid AND a.match_score IS NOT NULL
		    ORDER BY a.match_score DESC LIMIT :lim"
		);
		$stmt->bindValue(':jid', $jobId, PDO::PARAM_INT);
		$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	public function logStageChange(int $appId, ?string $from, string $to, int $actorId, ?string $reason = null): void
	{
		$stmt = $this->db->prepare(
		    "INSERT INTO pipeline_stages_log (application_id,from_stage,to_stage,actor_id,reason) VALUES(:aid,:fs,:ts,:act,:r)"
		);
		$stmt->execute(['aid' => $appId, 'fs' => $from, 'ts' => $to, 'act' => $actorId, 'r' => $reason]);
	}

	public function getStageLog(int $appId): array
	{
		$stmt = $this->db->prepare(
		    "SELECT p.*, u.name as actor_name FROM pipeline_stages_log p
		    JOIN users u ON p.actor_id=u.id WHERE p.application_id=:aid ORDER BY p.changed_at ASC"
		);
		$stmt->execute(['aid' => $appId]);
		return $stmt->fetchAll();
	}
}
