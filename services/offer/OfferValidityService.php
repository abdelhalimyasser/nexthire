<?php
declare(strict_types=1);
/** #31 Offer Validity Timer — State machine */
class OfferValidityService {
    private PDO $db;
    private AuditLogger $audit;
    public function __construct() { $this->db = Database::getInstance(); $this->audit = AuditLogger::getInstance(); }

    public function send(int $offerId): void {
        $hours = OFFER_DECISION_WINDOW_HOURS;
        $expires = date("Y-m-d H:i:s", strtotime("+{$hours} hours"));
        $this->db->prepare("UPDATE offers SET status='sent', expires_at=:exp WHERE id=:id AND status='pending'")->execute(["exp"=>$expires,"id"=>$offerId]);
        $this->audit->log(null,"offer",$offerId,"sent",[],["expires_at"=>$expires]);
    }

    public function checkExpiry(int $offerId): void {
        $stmt = $this->db->prepare("SELECT * FROM offers WHERE id=:id"); $stmt->execute(["id"=>$offerId]); $offer = $stmt->fetch();
        if ($offer && $offer["status"]==="sent" && $offer["expires_at"] && strtotime($offer["expires_at"]) < time()) {
            $this->db->prepare("UPDATE offers SET status='expired' WHERE id=:id")->execute(["id"=>$offerId]);
            $this->audit->log(null,"offer",$offerId,"expired",[],[]);
        }
    }

    public function accept(int $offerId): bool {
        $stmt = $this->db->prepare("UPDATE offers SET status='accepted' WHERE id=:id AND status='sent' AND (expires_at IS NULL OR expires_at >= NOW())");
        $stmt->execute(["id"=>$offerId]);
        if ($stmt->rowCount()) { $this->audit->log(null,"offer",$offerId,"accepted",[],[]); return true; }
        return false;
    }

    public function decline(int $offerId): bool {
        $stmt = $this->db->prepare("UPDATE offers SET status='declined' WHERE id=:id AND status='sent'");
        $stmt->execute(["id"=>$offerId]);
        if ($stmt->rowCount()) { $this->audit->log(null,"offer",$offerId,"declined",[],[]); return true; }
        return false;
    }
}
