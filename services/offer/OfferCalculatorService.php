<?php
declare(strict_types=1);
/** #29 Offer Package Calculator */
class OfferCalculatorService {
    public function calculate(string $candidateLevel, string $locationTier, string $roleType = ""): array {
        $base = SALARY_BANDS[$candidateLevel][$locationTier] ?? 50000;
        $bonus = round($base * SIGNING_BONUS_PCT, 2);
        $equity = EQUITY_UNITS[$candidateLevel] ?? 100;
        return ["base_salary" => $base, "signing_bonus" => $bonus, "equity_units" => $equity, "total_first_year" => $base + $bonus, "level" => $candidateLevel, "tier" => $locationTier];
    }
}
