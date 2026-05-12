<?php declare(strict_types=1);

namespace NextHire\Tests\Unit;

use NextHire\Tests\NextHireTestCase;

/**
 * ApplicationModelTest
 *
 * Tests:
 *  1. create() persists a new application row
 *  2. findByCandidate() returns only that candidate's applications
 *  3. logStageChange() writes a pipeline log row
 *  4. Stage transition reflected after update()
 *  5. findDuplicates() detects same email + same job
 *  6. Unique constraint prevents double-applying
 */
class ApplicationModelTest extends NextHireTestCase
{
    private \ApplicationModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new \ApplicationModel();
    }

    // ── 1. create() inserts row ───────────────────────────────────────────────
    public function testCreatePersistsApplication(): void
    {
        $adminId     = $this->seedUser(['role' => 'hr_admin', 'email' => 'adm1@test.com']);
        $candidateId = $this->seedUser(['email' => 'c_create@test.com']);
        $jobId       = $this->seedJob($adminId);

        $appId = $this->model->create([
            'job_id'       => $jobId,
            'candidate_id' => $candidateId,
            'stage'        => 'applied',
            'source'       => 'direct',
        ]);

        $this->assertGreaterThan(0, $appId);
        $row = $this->fetchOne('SELECT * FROM applications WHERE id = :id', ['id' => $appId]);
        $this->assertNotNull($row);
        $this->assertSame('applied', $row['stage']);
    }

    // ── 2. findByCandidate() returns only own applications ────────────────────
    public function testFindByCandidateReturnsOnlyOwnApplications(): void
    {
        $adminId = $this->seedUser(['role' => 'hr_admin', 'email' => 'adm2@test.com']);
        $c1      = $this->seedUser(['email' => 'c1_fbc@test.com']);
        $c2      = $this->seedUser(['email' => 'c2_fbc@test.com']);
        $jobId   = $this->seedJob($adminId);
        $jobId2  = $this->seedJob($adminId, ['title' => 'Second Job']);

        $this->seedApplication($jobId, $c1);
        $this->seedApplication($jobId2, $c2);

        $apps = $this->model->findByCandidate($c1);

        $this->assertCount(1, $apps);
        $this->assertSame((string)$c1, (string)$apps[0]['candidate_id']);
    }

    // ── 3. logStageChange() writes a pipeline log row ─────────────────────────
    public function testLogStageChangeWritesRecord(): void
    {
        $adminId     = $this->seedUser(['role' => 'hr_admin', 'email' => 'adm3@test.com']);
        $candidateId = $this->seedUser(['email' => 'c_log@test.com']);
        $jobId       = $this->seedJob($adminId);
        $appId       = $this->seedApplication($jobId, $candidateId);

        $this->model->logStageChange($appId, 'applied', 'screening', $adminId, 'Review passed');

        $log = $this->fetchOne(
            'SELECT * FROM pipeline_stages_log WHERE application_id = :id',
            ['id' => $appId]
        );
        $this->assertNotNull($log);
        $this->assertSame('applied',    $log['from_stage']);
        $this->assertSame('screening',  $log['to_stage']);
        $this->assertSame('Review passed', $log['reason']);
    }

    // ── 4. update() changes stage correctly ───────────────────────────────────
    public function testUpdateChangesStage(): void
    {
        $adminId     = $this->seedUser(['role' => 'hr_admin', 'email' => 'adm4@test.com']);
        $candidateId = $this->seedUser(['email' => 'c_upd@test.com']);
        $jobId       = $this->seedJob($adminId);
        $appId       = $this->seedApplication($jobId, $candidateId);

        $this->model->update($appId, ['stage' => 'interview']);

        $row = $this->fetchOne('SELECT stage FROM applications WHERE id = :id', ['id' => $appId]);
        $this->assertSame('interview', $row['stage']);
    }

    // ── 5. findDuplicates() returns matching applications ─────────────────────
    public function testFindDuplicatesDetectsSameEmailAndJob(): void
    {
        $adminId = $this->seedUser(['role' => 'hr_admin', 'email' => 'adm5@test.com']);
        $c1      = $this->seedUser(['email' => 'dupcandidate@test.com']);
        $jobId   = $this->seedJob($adminId);

        // First legitimate application
        $origId  = $this->seedApplication($jobId, $c1, ['stage' => 'screening']);

        // New application from same candidate / same job (different DB row — unique constraint
        // prevents same job_id+candidate_id, so this tests the email-based duplicate check)
        $dupes = $this->model->findDuplicates('dupcandidate@test.com', $jobId, $origId + 1);

        $this->assertIsArray($dupes);
        $this->assertNotEmpty($dupes, 'findDuplicates must detect same candidate+job');
    }

    // ── 6. Unique constraint prevents double-applying ─────────────────────────
    public function testUniqueConstraintPreventsDoubleApplying(): void
    {
        $adminId     = $this->seedUser(['role' => 'hr_admin', 'email' => 'adm6@test.com']);
        $candidateId = $this->seedUser(['email' => 'double@test.com']);
        $jobId       = $this->seedJob($adminId);

        $this->seedApplication($jobId, $candidateId);

        $this->expectException(\PDOException::class);
        $this->model->create([
            'job_id'       => $jobId,
            'candidate_id' => $candidateId,
            'stage'        => 'applied',
            'source'       => 'direct',
        ]);
    }
}
