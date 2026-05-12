<?php declare(strict_types=1);

namespace NextHire\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use PDO;

/**
 * NextHireTestCase
 *
 * Injects a fresh SQLite in-memory PDO into the Database singleton
 * before every test, completely isolating tests from the real MySQL DB.
 */
abstract class NextHireTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = createTestPdo();
        $this->injectPdo($this->pdo);
    }

    protected function tearDown(): void
    {
        // Reset the singleton so next test gets a fresh PDO
        $this->injectPdo(null);
        parent::tearDown();
    }

    // ── Inject any PDO (or null to reset) into Database::$instance ──────────
    protected function injectPdo(?PDO $pdo): void
    {
        $ref  = new ReflectionClass(\Database::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, $pdo);
    }

    // ── Seed helpers ──────────────────────────────────────────────────────────

    protected function seedUser(array $override = []): int
    {
        $data = array_merge([
            'name'          => 'Test User',
            'email'         => 'u_' . uniqid() . '@test.com',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT, ['cost' => 4]),
            'role'          => 'candidate',
            'is_active'     => 1,
        ], $override);
        return $this->insert('users', $data);
    }

    protected function seedJob(int $createdBy, array $override = []): int
    {
        return $this->insert('job_requisitions', array_merge([
            'title'        => 'PHP Developer',
            'department'   => 'Engineering',
            'description'  => 'Test job description',
            'requirements' => 'PHP, MySQL',
            'level'        => 'L3',
            'location_tier'=> 'tier1',
            'status'       => 'live',
            'created_by'   => $createdBy,
            'version'      => 1,
        ], $override));
    }

    protected function seedApplication(int $jobId, int $candidateId, array $override = []): int
    {
        return $this->insert('applications', array_merge([
            'job_id'       => $jobId,
            'candidate_id' => $candidateId,
            'stage'        => 'applied',
            'source'       => 'direct',
        ], $override));
    }

    protected function seedPanel(int $jobId, int $appId, array $override = []): int
    {
        return $this->insert('interview_panels', array_merge([
            'job_id'           => $jobId,
            'application_id'   => $appId,
            'scheduled_at'     => date('Y-m-d H:i:s', strtotime('+1 day')),
            'duration_minutes' => 60,
            'status'           => 'scheduled',
        ], $override));
    }

    protected function seedFeedback(int $panelId, int $interviewerId, int $candidateId, float $score = 7.5): int
    {
        return $this->insert('feedback_submissions', [
            'panel_id'       => $panelId,
            'interviewer_id' => $interviewerId,
            'candidate_id'   => $candidateId,
            'score'          => $score,
            'submitter_role' => 'interviewer',
            'include_in_score'=> 1,
            'submitted_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    // ── Generic insert helper ─────────────────────────────────────────────────
    protected function insert(string $table, array $data): int
    {
        $cols = implode(', ', array_keys($data));
        $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
        $stmt = $this->pdo->prepare("INSERT INTO $table ($cols) VALUES ($vals)");
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
