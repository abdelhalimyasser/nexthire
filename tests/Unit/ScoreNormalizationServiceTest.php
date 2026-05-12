<?php declare(strict_types=1);

namespace NextHire\Tests\Unit;

use NextHire\Tests\NextHireTestCase;

/**
 * ScoreNormalizationServiceTest
 *
 * Tests the harshness-correction algorithm:
 *   normalized = rawScore / (interviewerAvg / globalAvg)
 *
 * Tests:
 *  1. When interviewer avg == global avg → normalized == rawScore
 *  2. Harsh interviewer (avg below global) → normalized > rawScore
 *  3. Lenient interviewer (avg above global) → normalized < rawScore
 *  4. Zero interviewer_avg edge case → returns rawScore unchanged
 *  5. No historical data → returns rawScore unchanged
 */
class ScoreNormalizationServiceTest extends NextHireTestCase
{
    private \ScoreNormalizationService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new \ScoreNormalizationService();
    }

    // ── 1. Interviewer avg == global avg → score unchanged ────────────────────
    public function testNormalizationIsNeutralWhenRatiosMatch(): void
    {
        $interviewerId = $this->seedUser(['role' => 'interviewer', 'email' => 'iv_neutral@test.com']);
        $candidateId   = $this->seedUser(['role' => 'candidate',   'email' => 'c_neutral@test.com']);
        $hrId          = $this->seedUser(['role' => 'hr_admin',     'email' => 'hr_neutral@test.com']);
        $adminId       = $this->seedUser(['role' => 'hr_admin',     'email' => 'a_neutral@test.com']);
        $jobId         = $this->seedJob($adminId);
        $appId         = $this->seedApplication($jobId, $candidateId);
        $panelId       = $this->seedPanel($jobId, $appId);

        // Seed a submission with dimension scores that average to 8.0
        $subId = $this->seedFeedback($panelId, $interviewerId, $candidateId, 8.0);
        $this->insert('feedback_dimensions', ['submission_id' => $subId, 'dimension' => 'coding', 'score' => 8.0]);
        $this->insert('feedback_dimensions', ['submission_id' => $subId, 'dimension' => 'communication', 'score' => 8.0]);

        // seed a second submission from another interviewer — same score
        $subId2 = $this->seedFeedback($panelId, $hrId, $candidateId, 8.0);
        $this->insert('feedback_dimensions', ['submission_id' => $subId2, 'dimension' => 'coding',        'score' => 8.0]);
        $this->insert('feedback_dimensions', ['submission_id' => $subId2, 'dimension' => 'communication', 'score' => 8.0]);

        $normalized = $this->svc->normalize(8.0, $interviewerId);

        // Interviewer avg = 8.0, global avg = 8.0 → harshness = 1.0 → same
        $this->assertEqualsWithDelta(8.0, $normalized, 0.1);
    }

    // ── 2. Harsh interviewer → normalized score is higher ────────────────────
    public function testHarshInterviewerIncreasesNormalizedScore(): void
    {
        $harshId     = $this->seedUser(['role' => 'interviewer', 'email' => 'harsh@test.com']);
        $lenientId   = $this->seedUser(['role' => 'interviewer', 'email' => 'lenient@test.com']);
        $candidateId = $this->seedUser(['role' => 'candidate',   'email' => 'c_harsh@test.com']);
        $adminId     = $this->seedUser(['role' => 'hr_admin',    'email' => 'admin_harsh@test.com']);
        $jobId       = $this->seedJob($adminId);
        $appId       = $this->seedApplication($jobId, $candidateId);
        $panelId     = $this->seedPanel($jobId, $appId);

        // Harsh interviewer historically scores 5.0
        $sub1 = $this->seedFeedback($panelId, $harshId, $candidateId, 5.0);
        $this->insert('feedback_dimensions', ['submission_id' => $sub1, 'dimension' => 'coding', 'score' => 5.0]);

        // Lenient interviewer scores 9.0 → global avg = 7.0
        $sub2 = $this->seedFeedback($panelId, $lenientId, $candidateId, 9.0);
        $this->insert('feedback_dimensions', ['submission_id' => $sub2, 'dimension' => 'coding', 'score' => 9.0]);

        // harsh interviewer gives raw 6.0 → should normalize higher
        $normalized = $this->svc->normalize(6.0, $harshId);
        // harshness = 5.0/7.0 ≈ 0.714 → normalized = 6/0.714 ≈ 8.4
        $this->assertGreaterThan(6.0, $normalized, 'Harsh interviewer: normalized > raw');
    }

    // ── 3. Lenient interviewer → normalized score is lower ───────────────────
    public function testLenientInterviewerDecreasesNormalizedScore(): void
    {
        $lenientId   = $this->seedUser(['role' => 'interviewer', 'email' => 'l2@test.com']);
        $harshId     = $this->seedUser(['role' => 'interviewer', 'email' => 'h2@test.com']);
        $candidateId = $this->seedUser(['role' => 'candidate',   'email' => 'c_len@test.com']);
        $adminId     = $this->seedUser(['role' => 'hr_admin',    'email' => 'adm_len@test.com']);
        $jobId       = $this->seedJob($adminId);
        $appId       = $this->seedApplication($jobId, $candidateId);
        $panelId     = $this->seedPanel($jobId, $appId);

        // Lenient: avg 9.5; global avg ≈ 6.25
        $sub1 = $this->seedFeedback($panelId, $lenientId, $candidateId, 9.5);
        $this->insert('feedback_dimensions', ['submission_id' => $sub1, 'dimension' => 'coding', 'score' => 9.5]);

        $sub2 = $this->seedFeedback($panelId, $harshId, $candidateId, 3.0);
        $this->insert('feedback_dimensions', ['submission_id' => $sub2, 'dimension' => 'coding', 'score' => 3.0]);

        $normalized = $this->svc->normalize(9.0, $lenientId);
        $this->assertLessThan(9.0, $normalized, 'Lenient interviewer: normalized < raw');
    }

    // ── 4. No historical data → rawScore returned ────────────────────────────
    public function testNoHistoricalDataReturnsRawScore(): void
    {
        // Brand-new interviewer with no feedback history
        $newId = $this->seedUser(['role' => 'interviewer', 'email' => 'new_iv@test.com']);

        $normalized = $this->svc->normalize(7.5, $newId);

        $this->assertEqualsWithDelta(7.5, $normalized, 0.01);
    }
}
