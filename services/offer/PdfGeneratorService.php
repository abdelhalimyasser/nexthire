<?php declare(strict_types=1);
/**
 * PdfGeneratorService — Generates a clean, styled PDF offer letter.
 * No external libraries. Pure PHP binary PDF generation.
 */
class PdfGeneratorService {

    public function generateOfferLetter(int $offerId): string {
        $db   = Database::getInstance();
        $stmt = $db->prepare("
            SELECT o.*, a.candidate_id, jr.title as job_title, jr.department, jr.level,
                   u.name as candidate_name, u.email as candidate_email
            FROM offers o
            JOIN applications a ON o.application_id = a.id
            JOIN job_requisitions jr ON a.job_id = jr.id
            JOIN users u ON a.candidate_id = u.id
            WHERE o.id = :id
        ");
        $stmt->execute(["id" => $offerId]);
        $data = $stmt->fetch();
        if (!$data) throw new \RuntimeException("Offer #$offerId not found");

        if (!is_dir(PDF_OFFER_DIR)) mkdir(PDF_OFFER_DIR, 0777, true);
        $path = PDF_OFFER_DIR . "/offer_$offerId.pdf";
        file_put_contents($path, $this->buildPdf($data));
        $db->prepare("UPDATE offers SET pdf_path=:p WHERE id=:id")
           ->execute(["p" => "uploads/offers/offer_$offerId.pdf", "id" => $offerId]);
        return $path;
    }

    private function buildPdf(array $d): string {
        $name    = $d["candidate_name"];
        $job     = $d["job_title"];
        $dept    = $d["department"] ?? "Engineering";
        $level   = $d["level"]      ?? "";
        $email   = $d["candidate_email"] ?? "";
        $salary  = number_format((float)$d["salary"],        0, '.', ',');
        $bonus   = number_format((float)$d["signing_bonus"], 0, '.', ',');
        $equity  = $d["equity"] ?? "0";
        $date    = date("F j, Y");
        $year    = date("Y");
        $expiry  = date("F j, Y", strtotime("+7 days"));

        // Helper: encode PDF string (escape parentheses & backslashes)
        $ps = function(string $s): string {
            return str_replace(['\\','(',')'], ['\\\\','\\(','\\)'], $s);
        };

        // ── Build content streams for each page section ────────────────
        // Page 1: Header + greeting + package
        $s = "BT\n";

        // --- Indigo header band (filled rectangle) ---
        // We'll use BT for text only; shapes go outside BT block
        $s = "ET\n";
        // Header background
        $s .= "0.306 0.318 0.898 rg\n"; // indigo-600 #4f46e5
        $s .= "0 720 612 72 re f\n";

        // White logo-like "N" circle
        $s .= "1 1 1 rg\n";
        // Draw circle approximation with 8 bezier curves
        $cx = 50; $cy = 756; $r = 18;
        $s .= "$cx $cy m\n"; // just place text for simplicity

        // Header text (white)
        $s .= "BT\n";
        $s .= "/F2 22 Tf\n1 1 1 rg\n";
        $s .= "40 752 Td\n";
        $s .= "(NextHire) Tj\n";
        $s .= "/F1 10 Tf\n";
        $s .= "0 -22 Td\n";
        $s .= "(Official Offer Letter) Tj\n";

        // Date top-right (white)
        $s .= "/F1 9 Tf\n";
        $s .= "400 22 Td\n";
        $s .= "($date) Tj\n";
        $s .= "ET\n";

        // Light grey page background
        $s .= "0.973 0.976 0.980 rg\n"; // slate-50
        $s .= "0 0 612 720 re f\n";

        // White card area
        $s .= "1 1 1 rg\n";
        $s .= "40 40 532 665 re f\n";

        // Indigo left accent bar
        $s .= "0.306 0.318 0.898 rg\n";
        $s .= "40 40 5 665 re f\n";

        // ── Text content ────────────────────────────────────────────────
        $s .= "BT\n";
        $s .= "0.118 0.133 0.157 rg\n"; // slate-900

        // Greeting
        $s .= "/F2 16 Tf\n";
        $s .= "60 680 Td\n";
        $s .= "(Dear " . $ps($name) . ",) Tj\n";

        // Opening paragraph
        $s .= "/F1 10.5 Tf\n";
        $s .= "0 -24 Td\n";
        $s .= "(We are thrilled to extend to you this formal offer of employment with NextHire.) Tj\n";
        $s .= "0 -15 Td\n";
        $s .= "(Following a thorough review of your application and interviews, our team is) Tj\n";
        $s .= "0 -15 Td\n";
        $s .= "(confident that you will make an outstanding contribution to our organisation.) Tj\n";

        // Position section header
        $s .= "/F2 12 Tf\n";
        $s .= "0.306 0.318 0.898 rg\n"; // indigo
        $s .= "0 -28 Td\n";
        $s .= "(POSITION DETAILS) Tj\n";
        $s .= "0.118 0.133 0.157 rg\n";

        // Position details
        $s .= "/F1 10.5 Tf\n";
        $s .= "0 -16 Td\n(Job Title:      " . $ps($job) . ") Tj\n";
        $s .= "0 -14 Td\n(Department:   " . $ps($dept) . ") Tj\n";
        if ($level) {
            $s .= "0 -14 Td\n(Level:             " . $ps($level) . ") Tj\n";
        }
        $s .= "0 -14 Td\n(Start Date:     To be confirmed upon acceptance) Tj\n";
        $s .= "0 -14 Td\n(Location:        On-site / Hybrid) Tj\n";

        // Compensation header
        $s .= "/F2 12 Tf\n";
        $s .= "0.306 0.318 0.898 rg\n";
        $s .= "0 -28 Td\n(COMPENSATION PACKAGE) Tj\n";
        $s .= "0.118 0.133 0.157 rg\n";

        // Compensation box
        $s .= "ET\n";
        // light indigo box
        $s .= "0.925 0.933 0.996 rg\n"; // indigo-50
        // We need to know the current Y. Approximation:
        $s .= "55 395 512 82 re f\n";
        // Accent border left
        $s .= "0.306 0.318 0.898 rg\n";
        $s .= "55 395 3 82 re f\n";
        $s .= "0.118 0.133 0.157 rg\n";

        $s .= "BT\n";
        $s .= "/F2 11 Tf\n";
        $s .= "70 466 Td\n";
        $s .= "(Base Salary) Tj\n";
        $s .= "/F2 20 Tf\n";
        $s .= "0.306 0.318 0.898 rg\n";
        $s .= "0 -20 Td\n";
        $s .= "(\$$salary / year) Tj\n";
        $s .= "0.118 0.133 0.157 rg\n";

        $s .= "/F2 11 Tf\n";
        $s .= "240 466 Td\n";
        $s .= "(Signing Bonus) Tj\n";
        $s .= "/F2 16 Tf\n";
        $s .= "0.318 0.510 0.271 rg\n"; // green
        $s .= "0 -20 Td\n";
        $s .= "(\$$bonus) Tj\n";
        $s .= "0.118 0.133 0.157 rg\n";

        $s .= "/F2 11 Tf\n";
        $s .= "390 466 Td\n";
        $s .= "(Equity Units) Tj\n";
        $s .= "/F2 16 Tf\n";
        $s .= "0.549 0.376 0.855 rg\n"; // purple
        $s .= "0 -20 Td\n";
        $s .= "($equity units) Tj\n";
        $s .= "0.118 0.133 0.157 rg\n";

        // Conditions section
        $s .= "/F2 12 Tf\n";
        $s .= "0.306 0.318 0.898 rg\n";
        $s .= "60 385 Td\n";
        $s .= "(CONDITIONS OF OFFER) Tj\n";
        $s .= "0.118 0.133 0.157 rg\n";

        $s .= "/F1 10.5 Tf\n";
        $s .= "0 -16 Td\n";
        $s .= "(This offer is subject to:) Tj\n";
        $s .= "0 -14 Td\n(  \x95  Successful completion of a background verification check.) Tj\n";
        $s .= "0 -14 Td\n(  \x95  Satisfactory reference checks from previous employers.) Tj\n";
        $s .= "0 -14 Td\n(  \x95  Signing of the employment contract and NDA.) Tj\n";

        // Expiry
        $s .= "/F2 10.5 Tf\n";
        $s .= "0.792 0.231 0.227 rg\n"; // red
        $s .= "0 -22 Td\n";
        $s .= "(This offer expires on: " . $ps($expiry) . ") Tj\n";
        $s .= "0.118 0.133 0.157 rg\n";

        // Closing
        $s .= "/F1 10.5 Tf\n";
        $s .= "0 -22 Td\n";
        $s .= "(We look forward to welcoming you to the NextHire team. Please review) Tj\n";
        $s .= "0 -15 Td\n";
        $s .= "(the offer and respond at your earliest convenience.) Tj\n";

        // Signature block
        $s .= "/F2 11 Tf\n";
        $s .= "0 -30 Td\n";
        $s .= "(Warm regards,) Tj\n";
        $s .= "/F2 12 Tf\n";
        $s .= "0.306 0.318 0.898 rg\n";
        $s .= "0 -18 Td\n";
        $s .= "(NextHire HR Team) Tj\n";
        $s .= "0.118 0.133 0.157 rg\n";
        $s .= "/F1 9 Tf\n";
        $s .= "0 -14 Td\n";
        $s .= "(hr@nexthire.com   |   nexthire.com) Tj\n";

        // Footer
        $s .= "/F1 8 Tf\n0.537 0.573 0.620 rg\n"; // slate-500
        $s .= "40 55 Td\n";
        $s .= "(NextHire \x96 Confidential Offer Letter \x96 Generated $date \x96 Page 1 of 1) Tj\n";
        $s .= "ET\n";

        $sLen = strlen($s);

        // ── PDF Objects ────────────────────────────────────────────────
        // Font resources: F1 = Helvetica, F2 = Helvetica-Bold
        $fontDict = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $fontBoldDict = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        $obj1 = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $obj2 = "2 0 obj\n<< /Type /Pages /Kids [4 0 R] /Count 1 >>\nendobj\n";
        $obj3 = "3 0 obj\n<< /Length $sLen >>\nstream\n$s\nendstream\nendobj\n";
        $obj4 = "4 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 3 0 R"
              . " /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>\nendobj\n";
        $obj5 = "5 0 obj\n$fontDict\nendobj\n";
        $obj6 = "6 0 obj\n$fontBoldDict\nendobj\n";

        $header = "%PDF-1.4\n";
        $body   = $obj1 . $obj2 . $obj3 . $obj4 . $obj5 . $obj6;

        // ── Cross-reference table ──────────────────────────────────────
        $xrefOffset = strlen($header) + strlen($body);
        $pos = strlen($header);
        $xref = "xref\n0 7\n0000000000 65535 f \n";
        foreach ([$obj1,$obj2,$obj3,$obj4,$obj5,$obj6] as $o) {
            $xref .= str_pad((string)$pos, 10, "0", STR_PAD_LEFT) . " 00000 n \n";
            $pos  += strlen($o);
        }
        $trailer = "trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF\n";

        return $header . $body . $xref . $trailer;
    }
}