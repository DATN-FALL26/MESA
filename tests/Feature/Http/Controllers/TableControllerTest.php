<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\BranchStatus;
use App\Enums\ScopeType;
use App\Enums\SessionStatus;
use App\Enums\TableShape;
use App\Enums\TableStatus;
use App\Enums\UserStatus;
use App\Helpers\ConstantHelper;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DiningSession;
use App\Models\DiningTable;
use App\Models\Role;
use App\Models\SessionTable;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TableControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
    }

    public function test_table_map_renders_tables_grouped_by_area(): void
    {
        $table = $this->createTable('A01');

        $this->actingAs($this->createWaiter($table->branch))
            ->withSession(['branch_id' => $table->branch_id])
            ->withoutVite()
            ->get('/tables')
            ->assertOk()
            ->assertViewIs('tables.pos.index')
            ->assertSeeText('A01')
            ->assertSeeText('Khu A')
            ->assertSeeText('Mở phiên')
            ->assertSeeText('Bàn phục vụ')
            ->assertSeeText('Quản trị hệ thống')
            ->assertSee(route('tables.index'), false)
            ->assertSee('name="csrf-token"', false)
            ->assertDontSeeText('MESA POS')
            ->assertDontSee('data-modal="table-form"', false);
    }

    public function test_admin_table_map_uses_the_layout_management_view(): void
    {
        $table = $this->createTable('A01');

        $this->actingAs($this->createAdmin($table->branch))
            ->withSession(['branch_id' => $table->branch_id])
            ->withoutVite()
            ->get('/tables')
            ->assertOk()
            ->assertViewIs('tables.admin.index')
            ->assertSeeText('Quản lý sơ đồ bàn')
            ->assertSeeText('Quản trị hệ thống')
            ->assertSee(route('tables.index'), false)
            ->assertSee('name="csrf-token"', false)
            ->assertDontSeeText('MESA POS')
            ->assertDontSeeText('Mở phiên')
            ->assertDontSeeText('Đóng phiên, trả bàn trống')
            ->assertDontSeeText('Chuyển sang bàn khác')
            ->assertSee('data-modal="table-form"', false);
    }

    public function test_table_map_is_linked_from_the_inventory_layout(): void
    {
        $table = $this->createTable('A01');
        $admin = $this->createAdmin($table->branch);

        $this->actingAs($admin)
            ->withSession(['branch_id' => $table->branch_id])
            ->withoutVite()
            ->get('/inventory/adjustments')
            ->assertOk()
            ->assertSeeText('POS & Sơ đồ bàn')
            ->assertSee(route('tables.index'), false);
    }

    public function test_table_map_reports_open_sessions_as_occupied(): void
    {
        $table = $this->createTable('A01');
        $waiter = $this->createWaiter($table->branch);
        $session = $this->createOpenSession($table, $waiter);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->withoutVite()
            ->get('/tables')
            ->assertOk()
            ->assertSeeText('Phiên #'.$session->id)
            ->assertSeeText('2 khách')
            ->assertSeeText('Chuyển sang bàn khác')
            ->assertSeeText('Đóng phiên, trả bàn trống')
            ->assertViewHas('tables', function ($areas) use ($table): bool {
                $renderedTable = $areas->flatten()->firstWhere('id', $table->id);

                return $renderedTable?->status === TableStatus::OCCUPIED;
            });
    }

    public function test_admin_can_view_an_open_session_without_session_actions(): void
    {
            $table = $this->createTable('A01');
            $waiter = $this->createWaiter($table->branch);
            $session = $this->createOpenSession($table, $waiter);
            $admin = $this->createAdmin($table->branch);

            $this->actingAs($admin)
                ->withSession(['branch_id' => $table->branch_id])
                ->withoutVite()
                ->get('/tables')
                ->assertOk()
                ->assertSeeText('Phiên #'.$session->id)
                ->assertSeeText($waiter->full_name)
                ->assertDontSeeText('Mở phiên')
                ->assertDontSeeText('Đóng phiên, trả bàn trống')
                ->assertDontSeeText('Chuyển sang bàn khác')
                ->assertDontSee('data-modal="open-session"', false)
                ->assertDontSee('data-modal="transfer-session"', false);
    }

    public function test_table_map_shows_closed_session_transfer_history_and_hides_other_branches(): void
    {
        $table = $this->createTable('A02');
        $waiter = $this->createWaiter($table->branch);
        $session = $this->createOpenSession($table, $waiter);
        $session->update(['status' => SessionStatus::CLOSED, 'closed_at' => now()]);
        SessionTable::query()
            ->where('session_id', $session->id)
            ->update(['left_at' => now()]);
        $table->update(['status' => TableStatus::AVAILABLE]);
        AuditLog::query()->create([
            'branch_id' => $table->branch_id,
            'user_id' => $waiter->id,
            'action' => 'table_transfer',
            'entity_type' => 'dining_session',
            'entity_id' => $session->id,
            'old_value' => ['table_id' => 1, 'table_code' => 'A01'],
            'new_value' => ['table_id' => $table->id, 'table_code' => 'A02', 'transferred_by' => $waiter->id],
            'created_at' => '2026-05-20 14:30:00',
        ]);

        $otherBranch = $this->createBranch();
        AuditLog::query()->create([
            'branch_id' => $otherBranch->id,
            'user_id' => $this->createWaiter($otherBranch)->id,
            'action' => 'table_transfer',
            'entity_type' => 'dining_session',
            'entity_id' => 999999,
            'old_value' => ['table_id' => 1, 'table_code' => 'SECRET-FROM'],
            'new_value' => ['table_id' => 2, 'table_code' => 'SECRET-TO'],
            'created_at' => '2026-05-20 14:30:00',
        ]);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->withoutVite()
            ->get('/tables')
            ->assertOk()
            ->assertDontSeeText('Phiên #'.$session->id)
            ->assertSeeText('Lịch sử chuyển bàn')
            ->assertSeeText('A01 → A02')
            ->assertSeeText($waiter->full_name)
            ->assertSeeText('20/05/2026 14:30')
            ->assertDontSee('SECRET-FROM')
            ->assertDontSee('SECRET-TO');
    }

    public function test_status_endpoint_returns_only_tables_for_the_selected_area_and_branch(): void
    {
        $table = $this->createTable('A01');
        $this->createTable('A02', TableStatus::OCCUPIED, $table->branch, $table->area);
        $otherBranchTable = $this->createTable('B01');

        $this->actingAs($this->createWaiter($table->branch))
            ->withSession(['branch_id' => $table->branch_id])
            ->getJson('/api/tables/status?area_id='.$table->area_id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.code', 'A01')
            ->assertJsonMissing(['code' => $otherBranchTable->code]);
    }

    public function test_status_endpoint_reports_an_open_session_as_occupied(): void
    {
        $table = $this->createTable('A01');
        $waiter = $this->createWaiter($table->branch);
        $this->createOpenSession($table, $waiter);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->getJson('/api/tables/status')
            ->assertOk()
            ->assertJsonPath('data.0.status', TableStatus::OCCUPIED->value)
            ->assertJsonPath('data.0.open_session.guest_count', 2)
            ->assertJsonPath('data.0.open_session.employee_name', $waiter->full_name);
    }

    public function test_waiter_cannot_create_a_table_without_layout_management_permission(): void
    {
        $table = $this->createTable('A01');

        $this->actingAs($this->createWaiter($table->branch))
            ->withSession(['branch_id' => $table->branch_id])
            ->postJson('/api/tables', [
                'code' => 'A02',
                'branch_id' => $table->branch_id,
                'area_id' => $table->area_id,
                'seats' => 4,
                'status' => TableStatus::AVAILABLE->value,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('dining_tables', [
            'branch_id' => $table->branch_id,
            'code' => 'A02',
        ]);
    }

    public function test_valid_payload_creates_a_table(): void
    {
        $table = $this->createTable('A01');

        $this->actingAs($this->createAdmin($table->branch))
            ->withSession(['branch_id' => $table->branch_id])
            ->postJson('/api/tables', [
                'code' => 'A02',
                'branch_id' => $table->branch_id,
                'area_id' => $table->area_id,
                'seats' => 6,
                'status' => TableStatus::AVAILABLE->value,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'A02');

        $this->assertDatabaseHas('dining_tables', [
            'branch_id' => $table->branch_id,
            'area_id' => $table->area_id,
            'code' => 'A02',
            'seats' => 6,
        ]);
    }

    public function test_table_payload_requires_a_real_area_in_the_current_branch(): void
    {
        $table = $this->createTable('A01');
        $otherBranch = $this->createBranch();
        $otherArea = $this->createArea($otherBranch);

        $this->actingAs($this->createAdmin($table->branch))
            ->withSession(['branch_id' => $table->branch_id])
            ->postJson('/api/tables', [
                'code' => 'A02',
                'branch_id' => $table->branch_id,
                'area_id' => $otherArea->id,
                'seats' => 4,
                'status' => TableStatus::AVAILABLE->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['area_id']);
    }

    public function test_table_payload_rejects_invalid_capacity_and_status(): void
    {
        $table = $this->createTable('A01');

        $this->actingAs($this->createAdmin($table->branch))
            ->withSession(['branch_id' => $table->branch_id])
            ->postJson('/api/tables', [
                'code' => 'A02',
                'branch_id' => $table->branch_id,
                'area_id' => $table->area_id,
                'seats' => 0,
                'status' => 'reserved_for_later',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['seats', 'status']);
    }

    public function test_update_changes_table_details(): void
    {
        $table = $this->createTable('A01');

        $this->actingAs($this->createAdmin($table->branch))
            ->withSession(['branch_id' => $table->branch_id])
            ->putJson('/api/tables/'.$table->id, [
                'code' => 'A01B',
                'branch_id' => $table->branch_id,
                'area_id' => $table->area_id,
                'seats' => 8,
                'status' => TableStatus::CLEANING->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.code', 'A01B')
            ->assertJsonPath('data.seats', 8)
            ->assertJsonPath('data.status', TableStatus::CLEANING->value);

        $this->assertDatabaseHas('dining_tables', [
            'id' => $table->id,
            'code' => 'A01B',
            'seats' => 8,
            'status' => TableStatus::CLEANING->value,
        ]);
    }

    public function test_table_with_an_open_session_cannot_be_changed_to_available(): void
    {
        $table = $this->createTable('A01', TableStatus::OCCUPIED);
        $waiter = $this->createWaiter($table->branch);
        $session = $this->createOpenSession($table, $waiter);

        $this->actingAs($this->createAdmin($table->branch))
            ->withSession(['branch_id' => $table->branch_id])
            ->putJson('/api/tables/'.$table->id, [
                'code' => 'A01',
                'branch_id' => $table->branch_id,
                'area_id' => $table->area_id,
                'seats' => 4,
                'status' => TableStatus::AVAILABLE->value,
            ])
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bàn đang có phiên dùng bữa mở. Hãy đóng phiên trước khi đổi trạng thái bàn.');

        $this->assertDatabaseHas('dining_tables', [
            'id' => $table->id,
            'status' => TableStatus::OCCUPIED->value,
        ]);
        $this->assertDatabaseHas('session_tables', [
            'session_id' => $session->id,
            'table_id' => $table->id,
            'left_at' => null,
        ]);
    }

    public function test_table_can_be_deleted_when_not_in_use(): void
    {
        $table = $this->createTable('A01');

        $this->actingAs($this->createAdmin($table->branch))
            ->withSession(['branch_id' => $table->branch_id])
            ->deleteJson('/api/tables/'.$table->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('dining_tables', ['id' => $table->id]);
    }

    public function test_occupied_table_cannot_be_deleted(): void
    {
        $table = $this->createTable('A01', TableStatus::OCCUPIED);

        $this->actingAs($this->createAdmin($table->branch))
            ->withSession(['branch_id' => $table->branch_id])
            ->deleteJson('/api/tables/'.$table->id)
            ->assertConflict()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('dining_tables', ['id' => $table->id]);
    }

    public function test_table_with_an_open_session_cannot_be_deleted_even_if_its_status_is_available(): void
    {
        $table = $this->createTable('A01');
        $waiter = $this->createWaiter($table->branch);
        $session = $this->createOpenSession($table, $waiter);

        $this->actingAs($this->createAdmin($table->branch))
            ->withSession(['branch_id' => $table->branch_id])
            ->deleteJson('/api/tables/'.$table->id)
            ->assertConflict()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('dining_tables', ['id' => $table->id]);
        $this->assertDatabaseHas('session_tables', [
            'session_id' => $session->id,
            'table_id' => $table->id,
            'left_at' => null,
        ]);
    }

    public function test_table_from_another_branch_cannot_be_updated_or_deleted(): void
    {
        $otherBranchTable = $this->createTable('A01');
        $currentBranch = $this->createBranch();
        $admin = $this->createAdmin($currentBranch);

        $this->actingAs($admin)
            ->withSession(['branch_id' => $currentBranch->id])
            ->putJson('/api/tables/'.$otherBranchTable->id, [
                'code' => 'A02',
                'branch_id' => $currentBranch->id,
                'area_id' => $this->createArea($currentBranch)->id,
                'seats' => 4,
                'status' => TableStatus::AVAILABLE->value,
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->withSession(['branch_id' => $currentBranch->id])
            ->deleteJson('/api/tables/'.$otherBranchTable->id)
            ->assertNotFound();

        $this->assertDatabaseHas('dining_tables', [
            'id' => $otherBranchTable->id,
            'branch_id' => $otherBranchTable->branch_id,
        ]);
    }

    private function createTable(
        string $code,
        TableStatus $status = TableStatus::AVAILABLE,
        ?Branch $branch = null,
        ?Area $area = null,
    ): DiningTable {
        $branch ??= $this->createBranch();
        $area ??= $this->createArea($branch);

        return DiningTable::query()->create([
            'branch_id' => $branch->id,
            'area_id' => $area->id,
            'code' => $code,
            'seats' => 4,
            'shape' => TableShape::SQUARE,
            'status' => $status,
            'is_active' => true,
        ]);
    }

    private function createBranch(): Branch
    {
        $number = Branch::query()->count() + 1;

        return Branch::query()->create([
            'code' => 'TEST'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            'name' => 'Test Branch '.$number,
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => BranchStatus::ACTIVE,
        ]);
    }

    private function createArea(Branch $branch): Area
    {
        return Area::query()->create([
            'branch_id' => $branch->id,
            'code' => 'A',
            'name' => 'Khu A',
            'default_seats' => 4,
            'is_active' => true,
        ]);
    }

    private function createWaiter(Branch $branch): User
    {
        $number = User::query()->count() + 1;
        $user = User::query()->create([
            'username' => 'waiter'.$number,
            'employee_code' => 'EMP'.$number,
            'full_name' => 'Test Waiter '.$number,
            'password' => 'Password123!',
            'status' => UserStatus::ACTIVE,
        ]);
        $role = Role::query()->where('code', ConstantHelper::ROLE_WAITER)->firstOrFail();

        UserRole::query()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => ScopeType::BRANCH,
            'scope_id' => $branch->id,
            'valid_from' => now()->subMinute(),
        ]);

        return $user;
    }

    private function createAdmin(Branch $branch): User
    {
        $number = User::query()->count() + 1;
        $user = User::query()->create([
            'username' => 'admin'.$number,
            'employee_code' => 'EMP'.$number,
            'full_name' => 'Test Admin '.$number,
            'password' => 'Password123!',
            'status' => UserStatus::ACTIVE,
        ]);
        $role = Role::query()->where('code', ConstantHelper::ROLE_ADMIN)->firstOrFail();

        UserRole::query()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => ScopeType::BRANCH,
            'scope_id' => $branch->id,
            'valid_from' => now()->subMinute(),
        ]);

        return $user;
    }

    private function createOpenSession(DiningTable $table, User $waiter): DiningSession
    {
        $session = DiningSession::query()->create([
            'branch_id' => $table->branch_id,
            'opened_by' => $waiter->id,
            'opened_at' => now(),
            'guest_count' => 2,
            'status' => SessionStatus::OPEN,
        ]);

        SessionTable::query()->create([
            'session_id' => $session->id,
            'table_id' => $table->id,
            'joined_at' => now(),
        ]);

        return $session;
    }
}
