<?php
declare(strict_types=1);
/** #32 Counter-Offer Negotiation Tracker */
class NegotiationTrackerService {
    private OfferModel $om;
    public function __construct() { $this->om = new OfferModel(); }

    public function addRound(int $offerId, string $proposedBy, float $salary, string $notes = ""): array {
        $existing = $this->om->getNegotiations($offerId);
        $revision = count($existing) + 1;
        $id = $this->om->addNegotiation($offerId, $revision, $salary, $proposedBy, $notes);
        if ($proposedBy === "company") {
            Database::getInstance()->prepare("UPDATE offers SET salary=:s WHERE id=:id")->execute(["s"=>$salary,"id"=>$offerId]);
        }
        AuditLogger::getInstance()->log(null,"offer_negotiation",$id,"new_round",[],["revision"=>$revision,"proposed_by"=>$proposedBy,"salary"=>$salary]);
        return ["id"=>$id,"revision_number"=>$revision,"proposed_by"=>$proposedBy,"proposed_salary"=>$salary,"notes"=>$notes];
    }
}
