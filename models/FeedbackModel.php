<?php
declare(strict_types=1);

class FeedbackModel extends BaseModel {
	
	protected string $table = 'feedback_submissions';

	public function findByPanel(int $panelId): array {
		$stmt=$this->db->prepare("SELECT fs.*, u.name as interviewer_name FROM feedback_submissions fs JOIN users u ON fs.interviewer_id=u.id WHERE fs.panel_id=:pid");
		$stmt->execute(['pid'=>$panelId]);
		return $stmt->fetchAll();
	}

	public function getDimensions(int $submissionId): array {
		$stmt=$this->db->prepare("SELECT * FROM feedback_dimensions WHERE submission_id=:sid");
		$stmt->execute(['sid'=>$submissionId]);
		return $stmt->fetchAll();
	}

	public function addDimension(int $submissionId, string $dimension, float $score, ?string $notes=null): int {
		$stmt=$this->db->prepare("INSERT INTO feedback_dimensions (submission_id,dimension,score,notes) VALUES(:sid,:dim,:sc,:n) ON DUPLICATE KEY UPDATE score=:sc2, notes=:n2");
		$stmt->execute(['sid'=>$submissionId,'dim'=>$dimension,'sc'=>$score,'n'=>$notes,'sc2'=>$score,'n2'=>$notes]);
		return (int)$this->db->lastInsertId();
	}

	public function getPendingByInterviewer(int $userId): array {
		$stmt=$this->db->prepare("SELECT fs.*, ip.scheduled_at, j.title as job_title FROM feedback_submissions fs JOIN interview_panels ip ON fs.panel_id=ip.id JOIN job_requisitions j ON ip.job_id=j.id WHERE fs.interviewer_id=:uid AND fs.submitted_at IS NULL");
		$stmt->execute(['uid'=>$userId]);
		return $stmt->fetchAll();
	}

	public function getRedFlags(int $submissionId): array {
		$stmt=$this->db->prepare("SELECT * FROM red_flags WHERE submission_id=:sid");
		$stmt->execute(['sid'=>$submissionId]);
		return $stmt->fetchAll();
	}
    
	public function addRedFlag(int $submissionId, string $desc, string $severity): int {
		$stmt=$this->db->prepare("INSERT INTO red_flags (submission_id,description,severity) VALUES(:sid,:d,:s)");
		$stmt->execute(['sid'=>$submissionId,'d'=>$desc,'s'=>$severity]);
		return (int)$this->db->lastInsertId();
	}
}
