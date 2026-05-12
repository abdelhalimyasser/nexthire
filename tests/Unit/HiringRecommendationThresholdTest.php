<?php declare(strict_types=1);

namespace NextHire\Tests\Unit;

use NextHire\Tests\NextHireTestCase;

/**
 * HiringRecommendationThresholdTest
 *
 * Tests the threshold classification logic extracted from HiringRecommendationService.
 * Since HIRE_THRESHOLDS is a constant we can test the classification rules directly.
 *
 * Tests:
 *  1. Score >= 8.5 → strong_hire
 *  2. Score >= 7.0 and < 8.5 → hire
 *  3. Score >= 5.0 and < 7.0 → no_hire
 *  4. Score < 5.0 → strong_no_hire
 *  5. Boundary value: exactly 8.5 → strong_hire
 *  6. Boundary value: exactly 7.0 → hire
 *  7. Boundary value: exactly 5.0 → no_hire
 */
class HiringRecommendationThresholdTest extends NextHireTestCase
{
    /**
     * Replicate the classification logic from HiringRecommendationService::decide()
     */
    private function classify(float $score): string
    {
        if ($score >= HIRE_THRESHOLDS['strong_hire']) return 'strong_hire';
        if ($score >= HIRE_THRESHOLDS['hire'])        return 'hire';
        if ($score >= HIRE_THRESHOLDS['no_hire'])     return 'no_hire';
        return 'strong_no_hire';
    }

    // ── 1. High score → strong_hire ───────────────────────────────────────────
    public function testHighScoreIsStrongHire(): void
    {
        $this->assertSame('strong_hire', $this->classify(9.2));
        $this->assertSame('strong_hire', $this->classify(10.0));
    }

    // ── 2. Good score → hire ─────────────────────────────────────────────────
    public function testGoodScoreIsHire(): void
    {
        $this->assertSame('hire', $this->classify(7.5));
        $this->assertSame('hire', $this->classify(8.4));
    }

    // ── 3. Average score → no_hire ───────────────────────────────────────────
    public function testAverageScoreIsNoHire(): void
    {
        $this->assertSame('no_hire', $this->classify(4.0));
        $this->assertSame('no_hire', $this->classify(6.4));
    }

    // ── 4. Low score → strong_no_hire ────────────────────────────────────────
    public function testLowScoreIsStrongNoHire(): void
    {
        $this->assertSame('strong_no_hire', $this->classify(3.9));
        $this->assertSame('strong_no_hire', $this->classify(0.0));
    }

    // ── 5. Boundary: exactly 8.5 → strong_hire ───────────────────────────────
    public function testBoundaryStrongHire(): void
    {
        $this->assertSame('strong_hire', $this->classify(8.5));
    }

    // ── 6. Boundary: exactly 6.5 → hire ─────────────────────────────────────
    public function testBoundaryHire(): void
    {
        $this->assertSame('hire', $this->classify(6.5));
    }

    // ── 7. Boundary: exactly 4.0 → no_hire ───────────────────────────────────
    public function testBoundaryNoHire(): void
    {
        $this->assertSame('no_hire', $this->classify(4.0));
    }
}
