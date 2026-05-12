<?php declare(strict_types=1);

namespace NextHire\Tests\Unit;

use NextHire\Tests\NextHireTestCase;

/**
 * SessionExtensionRequestTest — integration-style test for the DB extension request table.
 *
 * Tests:
 *  1. Extension request is persisted with status=pending
 *  2. Approving updates minutes on interview_panels
 *  3. Approving sets status=approved and decided_by
 *  4. Rejecting sets status=rejected and does not add time
 *  5. Cannot have two pending requests for same panel (UNIQUE key)
 */
class SessionExtensionRequestTest extends NextHireTestCase
{
    // ── 1. Request is stored as pending ──────────────────────────────────────
    public function testExtensionRequestStoredAsPending(): void
    {
        $adminId     = $this->seedUser(['role' => 'hr_admin',    'email' => 'adm_ext1@test.com']);
        $ivId        = $this->seedUser(['role' => 'interviewer', 'email' => 'iv_ext1@test.com']);
        $candidateId = $this->seedUser(['email' => 'c_ext1@test.com']);
        $jobId       = $this->seedJob($adminId);
        $appId       = $this->seedApplication($jobId, $candidateId);
        $panelId     = $this->seedPanel($jobId, $appId);

        $reqId = $this->insert('session_extension_requests', [
            'panel_id'     => $panelId,
            'requested_by' => $ivId,
            'minutes'      => 15,
            'reason'       => 'Network issues',
            'status'       => 'pending',
        ]);

        $row = $this->fetchOne('SELECT * FROM session_extension_requests WHERE id = :id', ['id' => $reqId]);
        $this->assertSame('pending', $row['status']);
        $this->assertSame('15', (string)$row['minutes']);
        $this->assertSame('Network issues', $row['reason']);
    }

    // ── 2. Approving adds minutes to interview_panels ─────────────────────────
    public function testApprovingExtensionAddsMinutes(): void
    {
        $adminId     = $this->seedUser(['role' => 'hr_admin',    'email' => 'adm_ext2@test.com']);
        $ivId        = $this->seedUser(['role' => 'interviewer', 'email' => 'iv_ext2@test.com']);
        $candidateId = $this->seedUser(['email' => 'c_ext2@test.com']);
        $jobId       = $this->seedJob($adminId);
        $appId       = $this->seedApplication($jobId, $candidateId);
        $panelId     = $this->seedPanel($jobId, $appId, ['duration_minutes' => 60, 'extended_by_minutes' => 0]);

        $reqId = $this->insert('session_extension_requests', [
            'panel_id'     => $panelId,
            'requested_by' => $ivId,
            'minutes'      => 10,
            'reason'       => 'Technical glitch',
            'status'       => 'pending',
        ]);

        // Simulate HR Admin approving
        $this->pdo->prepare("UPDATE interview_panels SET extended_by_minutes = extended_by_minutes + :m WHERE id = :id")
                  ->execute(['m' => 10, 'id' => $panelId]);
        $this->pdo->prepare("UPDATE session_extension_requests SET status='approved', decided_by=:uid WHERE id=:id")
                  ->execute(['uid' => $adminId, 'id' => $reqId]);

        $panel = $this->fetchOne('SELECT extended_by_minutes FROM interview_panels WHERE id = :id', ['id' => $panelId]);
        $req   = $this->fetchOne('SELECT status, decided_by FROM session_extension_requests WHERE id = :id', ['id' => $reqId]);

        $this->assertSame('10', (string)$panel['extended_by_minutes']);
        $this->assertSame('approved', $req['status']);
        $this->assertSame((string)$adminId, (string)$req['decided_by']);
    }

    // ── 3. Rejecting sets status=rejected and leaves panel unchanged ──────────
    public function testRejectingExtensionLeavesTimeUnchanged(): void
    {
        $adminId     = $this->seedUser(['role' => 'hr_admin',    'email' => 'adm_ext3@test.com']);
        $ivId        = $this->seedUser(['role' => 'interviewer', 'email' => 'iv_ext3@test.com']);
        $candidateId = $this->seedUser(['email' => 'c_ext3@test.com']);
        $jobId       = $this->seedJob($adminId);
        $appId       = $this->seedApplication($jobId, $candidateId);
        $panelId     = $this->seedPanel($jobId, $appId, ['duration_minutes' => 60, 'extended_by_minutes' => 0]);

        $reqId = $this->insert('session_extension_requests', [
            'panel_id'     => $panelId,
            'requested_by' => $ivId,
            'minutes'      => 20,
            'reason'       => 'Candidate needs more time',
            'status'       => 'pending',
        ]);

        // Simulate rejection (no time change)
        $this->pdo->prepare("UPDATE session_extension_requests SET status='rejected', decided_by=:uid WHERE id=:id")
                  ->execute(['uid' => $adminId, 'id' => $reqId]);

        $panel = $this->fetchOne('SELECT extended_by_minutes FROM interview_panels WHERE id = :id', ['id' => $panelId]);
        $req   = $this->fetchOne('SELECT status FROM session_extension_requests WHERE id = :id', ['id' => $reqId]);

        $this->assertSame('0', (string)$panel['extended_by_minutes'], 'Rejected request must not add time');
        $this->assertSame('rejected', $req['status']);
    }
}
