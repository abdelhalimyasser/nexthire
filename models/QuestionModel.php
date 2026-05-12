<?php

declare(strict_types=1);

class QuestionModel extends BaseModel {
    
    protected string $table = 'questions';

    public function findByAssessment(int $assessmentId): array {
        return $this->findWhere('assessment_id=:aid', ['aid' => $assessmentId]);
    }

    public function findByDifficulty(string $difficulty, ?int $assessmentId = null): array {
        $w = 'difficulty=:d'; 
        $p = ['d' => $difficulty];

        if ($assessmentId) { 
            $w .= ' AND (assessment_id=:aid OR assessment_id IS NULL)'; $p['aid'] = $assessmentId; 
        }

        return $this->findWhere($w, $p);
    }
    
    public function findRandomByDifficulty(string $difficulty, int $limit, array $excludeIds = [], ?int $assessmentId = null): array {
        $exclude = $excludeIds ? implode(',', array_map('intval', $excludeIds)) : '0';
        $aq = $assessmentId ? "AND (assessment_id=:aid OR assessment_id IS NULL)" : "";
        
        $stmt = $this->db->prepare("SELECT * FROM questions WHERE difficulty=:d {$aq} AND id NOT IN ({$exclude}) ORDER BY RAND() LIMIT :lim");
        $stmt->bindValue(':d', $difficulty); $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        
        if ($assessmentId) 
            $stmt->bindValue(':aid', $assessmentId, PDO::PARAM_INT);

        $stmt->execute(); 
        
        return $stmt->fetchAll();
    }
}
