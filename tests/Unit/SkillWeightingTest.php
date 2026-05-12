<?php declare(strict_types=1);

namespace NextHire\Tests\Unit;

use NextHire\Tests\NextHireTestCase;

/**
 * WeightedAverageStrategyTest — pure algorithm, no DB needed.
 * NormalizedScoringStrategyTest — same.
 * SkillWeightingService is not tested in isolation here because it requires DB.
 *
 * Tests:
 *  1. Perfect match → 100%
 *  2. No match → 0%
 *  3. Partial match (weighted)
 *  4. Case-insensitive matching
 *  5. Empty job skills → 0%
 *  6. NormalizedScoringStrategy partial match
 *  7. NormalizedScoringStrategy full match
 */
class SkillWeightingTest extends NextHireTestCase
{
    private \WeightedAverageStrategy $weighted;
    private \NormalizedScoringStrategy $normalized;

    protected function setUp(): void
    {
        parent::setUp();
        $this->weighted   = new \WeightedAverageStrategy();
        $this->normalized = new \NormalizedScoringStrategy();
    }

    // ── 1. All skills match → 100% ────────────────────────────────────────────
    public function testWeightedPerfectMatchReturns100(): void
    {
        $jobSkills = [
            ['skill_name' => 'PHP',   'weight' => 9.0, 'is_required' => 1],
            ['skill_name' => 'MySQL', 'weight' => 8.0, 'is_required' => 1],
        ];
        $candidateSkills = ['PHP', 'MySQL'];

        $score = $this->weighted->calculate($jobSkills, $candidateSkills);

        $this->assertEqualsWithDelta(100.0, $score, 0.01);
    }

    // ── 2. No skill matches → 0% ─────────────────────────────────────────────
    public function testWeightedNoMatchReturns0(): void
    {
        $jobSkills = [
            ['skill_name' => 'PHP', 'weight' => 9.0, 'is_required' => 1],
        ];
        $score = $this->weighted->calculate($jobSkills, ['Python', 'Java']);

        $this->assertEqualsWithDelta(0.0, $score, 0.01);
    }

    // ── 3. Partial weighted match ─────────────────────────────────────────────
    public function testWeightedPartialMatchCalculatesCorrectly(): void
    {
        $jobSkills = [
            ['skill_name' => 'PHP',   'weight' => 8.0, 'is_required' => 1],
            ['skill_name' => 'MySQL', 'weight' => 2.0, 'is_required' => 0],
        ];
        // Total weight = 10, matched = PHP (8)
        $expected = round((8.0 / 10.0) * 100, 2); // 80.0

        $score = $this->weighted->calculate($jobSkills, ['PHP']);

        $this->assertEqualsWithDelta($expected, $score, 0.01);
    }

    // ── 4. Case-insensitive matching ──────────────────────────────────────────
    public function testWeightedIsCaseInsensitive(): void
    {
        $jobSkills = [['skill_name' => 'JavaScript', 'weight' => 1.0, 'is_required' => 1]];

        $scoreUpper = $this->weighted->calculate($jobSkills, ['JAVASCRIPT']);
        $scoreLower = $this->weighted->calculate($jobSkills, ['javascript']);
        $scoreMixed = $this->weighted->calculate($jobSkills, ['JavaScript']);

        $this->assertEqualsWithDelta(100.0, $scoreUpper, 0.01);
        $this->assertEqualsWithDelta(100.0, $scoreLower, 0.01);
        $this->assertEqualsWithDelta(100.0, $scoreMixed, 0.01);
    }

    // ── 5. Empty job skills → 0% ─────────────────────────────────────────────
    public function testWeightedEmptyJobSkillsReturns0(): void
    {
        $score = $this->weighted->calculate([], ['PHP', 'MySQL']);
        $this->assertEqualsWithDelta(0.0, $score, 0.01);
    }

    // ── 6. Normalized strategy — partial match ────────────────────────────────
    public function testNormalizedPartialMatch(): void
    {
        $jobSkills = [
            ['skill_name' => 'PHP',    'weight' => 1.0, 'is_required' => 1],
            ['skill_name' => 'MySQL',  'weight' => 1.0, 'is_required' => 0],
            ['skill_name' => 'Docker', 'weight' => 1.0, 'is_required' => 0],
            ['skill_name' => 'Redis',  'weight' => 1.0, 'is_required' => 0],
        ];
        // Candidate has 2 of 4 → 50%
        $score = $this->normalized->calculate($jobSkills, ['PHP', 'MySQL']);
        $this->assertEqualsWithDelta(50.0, $score, 0.01);
    }

    // ── 7. Normalized strategy — full match → 100% ────────────────────────────
    public function testNormalizedFullMatchReturns100(): void
    {
        $jobSkills = [
            ['skill_name' => 'PHP',   'weight' => 1.0, 'is_required' => 1],
            ['skill_name' => 'MySQL', 'weight' => 1.0, 'is_required' => 1],
        ];
        $score = $this->normalized->calculate($jobSkills, ['PHP', 'MySQL', 'Docker']);
        $this->assertEqualsWithDelta(100.0, $score, 0.01);
    }
}
