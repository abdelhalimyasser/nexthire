<?php
declare(strict_types=1);
/** #33 Referral Reward Attribution */
class ReferralService {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function attribute(int $applicationId): ?array {
        $stmt = $this->db->prepare("SELECT referral_code FROM applications WHERE id=:aid"); $stmt->execute(["aid"=>$applicationId]);
        $app = $stmt->fetch(); if (!$app || empty($app["referral_code"])) return null;

        $refStmt = $this->db->prepare("SELECT * FROM users WHERE email=:code OR id=:code2"); 
        $refStmt->execute(["code"=>$app["referral_code"],"code2"=>(int)$app["referral_code"]]);
        $referrer = $refStmt->fetch(); if (!$referrer) return null;

        $triggerDate = date("Y-m-d", strtotime("+" . REFERRAL_BONUS_DELAY_DAYS . " days"));
        $this->db->prepare("INSERT INTO referrals (referred_by_user_id,candidate_id,application_id,bonus_amount,bonus_trigger_date,status) VALUES(:rb,:cid,:aid,:amt,:td,'pending')")
            ->execute(["rb"=>$referrer["id"],"cid"=>$app["referral_code"],"aid"=>$applicationId,"amt"=>REFERRAL_BONUS_AMOUNT,"td"=>$triggerDate]);
        return ["referrer"=>$referrer["name"],"trigger_date"=>$triggerDate,"amount"=>REFERRAL_BONUS_AMOUNT];
    }

    public function processDueRewards(): array {
        $stmt = $this->db->prepare("SELECT * FROM referrals WHERE status='pending' AND bonus_trigger_date <= CURDATE()");
        $stmt->execute(); $due = $stmt->fetchAll(); $processed = [];
        foreach ($due as $ref) {
            $referrer = (new UserModel())->findById((int)$ref["referred_by_user_id"]);
            if ($referrer && $referrer["is_active"]) {
                $this->db->prepare("UPDATE referrals SET status='due', bonus_triggered_at=NOW() WHERE id=:id")->execute(["id"=>$ref["id"]]);
                $processed[] = $ref;
            } else {
                $this->db->prepare("UPDATE referrals SET status='flagged' WHERE id=:id")->execute(["id"=>$ref["id"]]);
            }
        }
        return $processed;
    }
}
