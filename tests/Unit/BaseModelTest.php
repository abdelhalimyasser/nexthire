<?php declare(strict_types=1);

namespace NextHire\Tests\Unit;

use NextHire\Tests\NextHireTestCase;

/**
 * BaseModelTest
 *
 * Tests the generic CRUD helpers in BaseModel using UserModel as the proxy.
 *
 * Tests:
 *  1. findById() — happy path
 *  2. findById() — returns null for missing ID
 *  3. findAll() — returns rows ordered descending
 *  4. findAll() — respects limit
 *  5. update() — changes fields
 *  6. delete() — removes row
 *  7. create() — returns auto-increment ID
 */
class BaseModelTest extends NextHireTestCase
{
    // UserModel extends BaseModel — use it as a concrete vehicle for testing
    private \UserModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new \UserModel();
    }

    // ── 1. findById happy path ────────────────────────────────────────────────
    public function testFindByIdReturnRow(): void
    {
        $id = $this->seedUser(['name' => 'Alpha', 'email' => 'alpha@base.com']);

        $row = $this->model->findById($id);

        $this->assertIsArray($row);
        $this->assertSame('Alpha', $row['name']);
    }

    // ── 2. findById with missing id ───────────────────────────────────────────
    public function testFindByIdReturnsNullForMissingId(): void
    {
        $result = $this->model->findById(987654);
        $this->assertNull($result);
    }

    // ── 3. findAll returns rows ────────────────────────────────────────────────
    public function testFindAllReturnsSeedRows(): void
    {
        $this->seedUser(['email' => 'fa1@base.com']);
        $this->seedUser(['email' => 'fa2@base.com']);

        $rows = $this->model->findAll();

        $this->assertGreaterThanOrEqual(2, count($rows));
    }

    // ── 4. findAll respects limit ─────────────────────────────────────────────
    public function testFindAllRespectsLimit(): void
    {
        $this->seedUser(['email' => 'lim1@base.com']);
        $this->seedUser(['email' => 'lim2@base.com']);
        $this->seedUser(['email' => 'lim3@base.com']);

        $rows = $this->model->findAll('id', 'DESC', 2, 0);

        $this->assertCount(2, $rows);
    }

    // ── 5. update() changes fields ────────────────────────────────────────────
    public function testUpdateChangesField(): void
    {
        $id = $this->seedUser(['name' => 'Before', 'email' => 'before@base.com']);

        $this->model->update($id, ['name' => 'After']);

        $row = $this->model->findById($id);
        $this->assertSame('After', $row['name']);
    }

    // ── 6. delete() removes row ───────────────────────────────────────────────
    public function testDeleteRemovesRow(): void
    {
        $id = $this->seedUser(['email' => 'del@base.com']);

        $result = $this->model->delete($id);

        $this->assertTrue($result);
        $this->assertNull($this->model->findById($id));
    }

    // ── 7. create() returns new ID ─────────────────────────────────────────────
    public function testCreateReturnsPositiveId(): void
    {
        $id = $this->model->create([
            'name'          => 'New User',
            'email'         => 'new_' . uniqid() . '@base.com',
            'password_hash' => password_hash('x', PASSWORD_BCRYPT, ['cost' => 4]),
            'role'          => 'candidate',
        ]);

        $this->assertGreaterThan(0, $id);
    }
}
