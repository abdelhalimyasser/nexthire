<?php
declare(strict_types=1);
/** #30 Digital Offer-Letter Generator — Template Method */
class OfferLetterService {
    public function generate(int $offerId): string {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT o.*, a.candidate_id, j.title as job_title, j.department, j.level, u.name as candidate_name, u.email FROM offers o JOIN applications a ON o.application_id=a.id JOIN job_requisitions j ON a.job_id=j.id JOIN users u ON a.candidate_id=u.id WHERE o.id=:oid");
        $stmt->execute(["oid" => $offerId]);
        $data = $stmt->fetch();
        if (!$data) throw new \DomainException("Offer not found");

        $date = date("F j, Y");
        $salary = number_format((float)$data["salary"], 2);
        $bonus = number_format((float)$data["signing_bonus"], 2);
        $equity = $data["equity"];
        $expires = $data["expires_at"] ? date("F j, Y", strtotime($data["expires_at"])) : "N/A";

        return '<div class="max-w-3xl mx-auto bg-white p-12 shadow-lg border">
            <div class="text-center mb-8"><h1 class="text-3xl font-bold text-indigo-700">NextHire Inc.</h1><p class="text-slate-500">Official Offer Letter</p></div>
            <p class="mb-4">Date: ' . $date . '</p>
            <p class="mb-4">Dear <strong>' . htmlspecialchars($data["candidate_name"]) . '</strong>,</p>
            <p class="mb-4">We are delighted to extend an offer for the position of <strong>' . htmlspecialchars($data["job_title"]) . '</strong> in the <strong>' . htmlspecialchars($data["department"]) . '</strong> department.</p>
            <div class="bg-slate-50 p-6 rounded-lg my-6">
                <h3 class="font-semibold text-lg mb-3">Compensation Package</h3>
                <table class="w-full"><tr><td class="py-1">Base Salary:</td><td class="font-semibold">$' . $salary . '/year</td></tr>
                <tr><td class="py-1">Signing Bonus:</td><td class="font-semibold">$' . $bonus . '</td></tr>
                <tr><td class="py-1">Equity:</td><td class="font-semibold">' . $equity . ' units</td></tr></table>
            </div>
            <p class="mb-4">This offer is valid until <strong>' . $expires . '</strong>.</p>
            <p class="mb-8">We look forward to welcoming you to the team!</p>
            <div class="mt-12 border-t pt-6"><p>Sincerely,<br><strong>HR Department</strong><br>NextHire Inc.</p></div>
            <div class="mt-8 border-t pt-4"><p class="text-sm text-slate-500">Candidate Signature: _________________________ Date: _____________</p></div>
        </div>';
    }
}
