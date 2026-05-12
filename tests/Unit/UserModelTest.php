<?php declare(strict_types=1);

namespace NextHire\Tests\Unit;

use NextHire\Tests\NextHireTestCase;

/**
 * UserModelTest — covers authentication, CRUD, role queries, anonymization.
 *
 * Tests:
 *  1. authenticate() — correct password returns user
 *  2. authenticate() — wrong password returns null
 *  3. authenticate() — inactive user returns null
 *  4. findByEmail() — finds existing user
 *  5. findByEmail() — returns null for unknown email
 *  6. findById() — returns user row
 *  7. findById() — returns null for unknown id
 *  8. findByRole() — returns only active users with that role
 *  9. anonymize() — blanks PII and deactivates
 * 10. create() / update() via BaseModel
 */
class UserModelTest extends NextHireTestCase
{
    private \UserModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new \UserModel();
    }

    // ── 1. Correct password authenticates ────────────────────────────────────
    public function testAuthenticateSuccessWithCorrectPassword(): void
    {
        $hash = password_hash('secret', PASSWORD_BCRYPT, ['cost' => 4]);
        $id   = $this->seedUser(['email' => 'auth@test.com', 'password_hash' => $hash]);

        $user = $this->model->authenticate('auth@test.com', 'secret');

        $this->assertIsArray($user, 'authenticate() must return an array on success');
        $this->assertSame($id, (int)$user['id']);
        $this->assertSame('auth@test.com', $user['email']);
    }

    // ── 2. Wrong password returns null ────────────────────────────────────────
    public function testAuthenticateFailsWithWrongPassword(): void
    {
        $hash = password_hash('correct', PASSWORD_BCRYPT, ['cost' => 4]);
        $this->seedUser(['email' => 'wrong@test.com', 'password_hash' => $hash]);

        $result = $this->model->authenticate('wrong@test.com', 'incorrect');

        $this->assertNull($result);
    }

    // ── 3. Inactive user is not authenticated ────────────────────────────────
    public function testAuthenticateRejectsInactiveUser(): void
    {
        $hash = password_hash('pass', PASSWORD_BCRYPT, ['cost' => 4]);
        $this->seedUser(['email' => 'inactive@test.com', 'password_hash' => $hash, 'is_active' => 0]);

        $result = $this->model->authenticate('inactive@test.com', 'pass');

        $this->assertNull($result, 'Inactive users must not be authenticated');
    }

    // ── 4. findByEmail() finds existing user ─────────────────────────────────
    public function testFindByEmailReturnsExistingUser(): void
    {
        $id = $this->seedUser(['email' => 'lookup@test.com']);

        $user = $this->model->findByEmail('lookup@test.com');

        $this->assertIsArray($user);
        $this->assertSame($id, (int)$user['id']);
    }

    // ── 5. findByEmail() returns null for unknown email ───────────────────────
    public function testFindByEmailReturnsNullForUnknownEmail(): void
    {
        $result = $this->model->findByEmail('nobody@example.com');
        $this->assertNull($result);
    }

    // ── 6. findById() returns correct row ────────────────────────────────────
    public function testFindByIdReturnsCorrectUser(): void
    {
        $id   = $this->seedUser(['name' => 'Jane Doe', 'email' => 'jane@test.com']);
        $user = $this->model->findById($id);

        $this->assertIsArray($user);
        $this->assertSame('Jane Doe', $user['name']);
    }

    // ── 7. findById() returns null for unknown id ─────────────────────────────
    public function testFindByIdReturnsNullForUnknownId(): void
    {
        $result = $this->model->findById(999999);
        $this->assertNull($result);
    }

    // ── 8. findByRole() returns only active users with matching role ──────────
    public function testFindByRoleFiltersCorrectly(): void
    {
        $this->seedUser(['role' => 'interviewer', 'email' => 'iv1@test.com', 'is_active' => 1]);
        $this->seedUser(['role' => 'interviewer', 'email' => 'iv2@test.com', 'is_active' => 0]); // inactive
        $this->seedUser(['role' => 'hr_admin',    'email' => 'hr@test.com',  'is_active' => 1]);

        $interviewers = $this->model->findByRole('interviewer');

        $this->assertCount(1, $interviewers, 'findByRole must exclude inactive users');
        $this->assertSame('iv1@test.com', $interviewers[0]['email']);
    }

    // ── 9. anonymize() blanks PII and deactivates ────────────────────────────
    public function testAnonymizeBlanksPiiAndDeactivates(): void
    {
        $id = $this->seedUser(['name' => 'Alice Smith', 'email' => 'alice@secret.com']);

        $result = $this->model->anonymize($id);

        $this->assertTrue($result);
        $user = $this->model->findById($id);
        $this->assertSame('[Anonymized]', $user['name']);
        $this->assertStringContainsString('removed.local', $user['email']);
        $this->assertSame(0, (int)$user['is_active']);
    }

    // ── 10. BaseModel::create() and update() round-trip ──────────────────────
    public function testCreateAndUpdateRoundTrip(): void
    {
        $id = $this->seedUser(['name' => 'Original', 'email' => 'orig@test.com']);

        $this->model->update($id, ['name' => 'Updated Name']);
        $user = $this->model->findById($id);

        $this->assertSame('Updated Name', $user['name']);
    }
}
