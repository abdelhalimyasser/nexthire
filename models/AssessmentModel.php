<?php
declare(strict_types=1);

class AssessmentModel extends BaseModel
{
    protected string $table = 'assessments';

    public function findByJob(int $jobId): array
    {
        return $this->findWhere('job_id=:jid', ['jid' => $jobId]);
    }
}
