<?php declare(strict_types=1);

namespace NextHire\Tests\Unit;

use NextHire\Tests\NextHireTestCase;

/**
 * PdfGeneratorServiceTest
 *
 * Tests:
 *  1. buildPdf() returns a string starting with %PDF-
 *  2. PDF output contains candidate name
 *  3. PDF output contains salary formatted as a number
 *  4. generateOfferLetter() writes a file to PDF_OFFER_DIR
 *  5. generateOfferLetter() updates the pdf_path in the DB
 *  6. generateOfferLetter() throws RuntimeException for unknown offer
 */
class PdfGeneratorServiceTest extends NextHireTestCase
{
    private \PdfGeneratorService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new \PdfGeneratorService();

        // Ensure the test output dir exists
        if (!is_dir(PDF_OFFER_DIR)) {
            mkdir(PDF_OFFER_DIR, 0777, true);
        }
    }

    // ── Helper: seed a full offer chain ──────────────────────────────────────
    private function seedOfferChain(): array
    {
        $adminId     = $this->seedUser(['role' => 'hr_admin', 'email' => 'adm_pdf@test.com']);
        $candidateId = $this->seedUser([
            'name'  => 'Alice Smith',
            'email' => 'alice_pdf@test.com',
            'role'  => 'candidate',
        ]);
        $jobId = $this->seedJob($adminId, [
            'title'      => 'Senior PHP Developer',
            'department' => 'Engineering',
            'level'      => 'L4',
        ]);
        $appId   = $this->seedApplication($jobId, $candidateId);
        $offerId = $this->insert('offers', [
            'application_id' => $appId,
            'salary'         => 95000.00,
            'signing_bonus'  => 5000.00,
            'equity'         => 100,
            'status'         => 'pending',
            'created_by'     => $adminId,
        ]);
        return compact('offerId', 'candidateId', 'jobId', 'appId', 'adminId');
    }

    // ── 1. PDF starts with %PDF- magic bytes ─────────────────────────────────
    public function testPdfOutputStartsWithMagicBytes(): void
    {
        $data = [
            'candidate_name'  => 'Test Candidate',
            'candidate_email' => 'tc@test.com',
            'job_title'       => 'Engineer',
            'department'      => 'Engineering',
            'level'           => 'L3',
            'salary'          => '90000',
            'signing_bonus'   => '5000',
            'equity'          => '50',
        ];

        $ref    = new \ReflectionClass($this->svc);
        $method = $ref->getMethod('buildPdf');
        $method->setAccessible(true);

        $pdf = $method->invoke($this->svc, $data);

        $this->assertStringStartsWith('%PDF-', $pdf, 'PDF output must start with %PDF- magic bytes');
    }

    // ── 2. PDF contains candidate name ────────────────────────────────────────
    public function testPdfContainsCandidateName(): void
    {
        $data = [
            'candidate_name'  => 'John Doe',
            'candidate_email' => 'jd@test.com',
            'job_title'       => 'Developer',
            'department'      => 'Engineering',
            'level'           => 'L3',
            'salary'          => '80000',
            'signing_bonus'   => '0',
            'equity'          => '0',
        ];

        $ref    = new \ReflectionClass($this->svc);
        $method = $ref->getMethod('buildPdf');
        $method->setAccessible(true);

        $pdf = $method->invoke($this->svc, $data);

        $this->assertStringContainsString('John Doe', $pdf, 'PDF must contain candidate name');
    }

    // ── 3. PDF contains salary ────────────────────────────────────────────────
    public function testPdfContainsSalaryAmount(): void
    {
        $data = [
            'candidate_name'  => 'Jane',
            'candidate_email' => 'jane@t.com',
            'job_title'       => 'Dev',
            'department'      => 'Eng',
            'level'           => 'L3',
            'salary'          => '120000',
            'signing_bonus'   => '0',
            'equity'          => '0',
        ];

        $ref    = new \ReflectionClass($this->svc);
        $method = $ref->getMethod('buildPdf');
        $method->setAccessible(true);

        $pdf = $method->invoke($this->svc, $data);

        $this->assertStringContainsString('120,000', $pdf, 'PDF must contain formatted salary');
    }

    // ── 4. generateOfferLetter() writes file ─────────────────────────────────
    public function testGenerateOfferLetterCreatesFile(): void
    {
        $chain   = $this->seedOfferChain();
        $offerId = $chain['offerId'];

        $path = $this->svc->generateOfferLetter($offerId);

        $this->assertFileExists($path, 'generateOfferLetter() must create the PDF file');
        // Cleanup
        @unlink($path);
    }

    // ── 5. generateOfferLetter() updates pdf_path in DB ──────────────────────
    public function testGenerateOfferLetterUpdatesPdfPathInDb(): void
    {
        $chain   = $this->seedOfferChain();
        $offerId = $chain['offerId'];

        $path = $this->svc->generateOfferLetter($offerId);

        $row = $this->fetchOne('SELECT pdf_path FROM offers WHERE id = :id', ['id' => $offerId]);
        $this->assertNotNull($row['pdf_path'], 'pdf_path must be set after generation');
        $this->assertStringContainsString("offer_$offerId", $row['pdf_path']);

        @unlink($path);
    }

    // ── 6. Unknown offer throws RuntimeException ──────────────────────────────
    public function testGenerateOfferLetterThrowsForUnknownOffer(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->svc->generateOfferLetter(999999);
    }
}
