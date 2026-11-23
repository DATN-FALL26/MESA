<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\BranchStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
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
use App\Models\Order;
use App\Models\Payment;
use App\Models\Role;
use App\Models\SessionTable;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DiningSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
    }

    public function test_valid_request_opens_a_session_and_marks_the_table_occupied(): void
    {
        $table = $this->createTable();
        $waiter = $this->createWaiter($table->branch);

        $response = $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->postJson('/api/dining-sessions', [
                'table_id' => $table->id,
                'guest_count' => 3,
                'opened_by' => $waiter->id,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.table_id', $table->id)
            ->assertJsonPath('data.guest_count', 3)
            ->assertJsonPath('data.opened_by', $waiter->id)
            ->assertJsonPath('data.status', SessionStatus::OPEN->value);

        $sessionId = $response->json('data.id');
        $this->assertDatabaseHas('dining_sessions', [
            'id' => $sessionId,
            'branch_id' => $table->branch_id,
            'opened_by' => $waiter->id,
            'guest_count' => 3,
            'status' => SessionStatus::OPEN->value,
        ]);
        $this->assertDatabaseHas('session_tables', [
            'session_id' => $sessionId,
            'table_id' => $table->id,
            'left_at' => null,
        ]);
        $this->assertSame(TableStatus::OCCUPIED, $table->fresh()->status);
    }

    public function test_open_session_requires_a_positive_guest_count(): void
    {
        $table = $this->createTable();
        $waiter = $this->createWaiter($table->branch);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->postJson('/api/dining-sessions', [
                'table_id' => $table->id,
                'guest_count' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['guest_count']);

        $this->assertDatabaseCount('dining_sessions', 0);
    }

    public function test_open_session_rejects_a_table_that_is_not_available(): void
    {
        $table = $this->createTable(TableStatus::CLEANING);
        $waiter = $this->createWaiter($table->branch);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->postJson('/api/dining-sessions', [
                'table_id' => $table->id,
                'guest_count' => 2,
            ])
            ->assertConflict()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('dining_sessions', 0);
    }

    public function test_open_session_rejects_a_table_with_an_active_session_even_if_marked_available(): void
    {
        $table = $this->createTable();
        $waiter = $this->createWaiter($table->branch);
        $session = $this->createOpenSession($table, $waiter);
        $table->update(['status' => TableStatus::AVAILABLE]);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->postJson('/api/dining-sessions', [
                'table_id' => $table->id,
                'guest_count' => 2,
            ])
            ->assertConflict()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('dining_sessions', 1);
        $this->assertDatabaseHas('session_tables', [
            'session_id' => $session->id,
            'table_id' => $table->id,
            'left_at' => null,
        ]);
    }

    public function test_closing_a_table_session_releases_the_table_and_records_the_session_line(): void
    {
        $table = $this->createTable(TableStatus::OCCUPIED);
        $waiter = $this->createWaiter($table->branch);
        $session = $this->createOpenSession($table, $waiter);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->postJson("/api/tables/{$table->id}/dining-session/close")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $session->id)
            ->assertJsonPath('data.status', SessionStatus::CLOSED->value);

        $this->assertDatabaseHas('dining_sessions', [
            'id' => $session->id,
            'status' => SessionStatus::CLOSED->value,
        ]);
        $this->assertDatabaseHas('session_tables', [
            'session_id' => $session->id,
            'table_id' => $table->id,
        ]);
        $this->assertNotNull($session->fresh()->closed_at);
        $this->assertNotNull(SessionTable::query()->where('session_id', $session->id)->firstOrFail()->left_at);
        $this->assertSame(TableStatus::CLEANING, $table->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'dining_session',
            'entity_id' => $session->id,
            'action' => 'dining_session_close',
        ]);
    }

    public function test_ready_paid_items_do_not_block_closing_the_session(): void
    {
        $table = $this->createTable(TableStatus::OCCUPIED);
        $waiter = $this->createWaiter($table->branch);
        $session = $this->createOpenSession($table, $waiter);
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Test Category',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $menuItemId = DB::table('menu_items')->insertGetId([
            'category_id' => $categoryId,
            'sku' => 'TEST-READY-CLOSE',
            'name' => 'Test Ready Dish',
            'item_type' => 'dish',
            'station_code' => 'PHO',
            'base_price' => 50000,
            'tax_rate' => 8,
            'prep_time_seconds' => 300,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $order = Order::query()->create([
            'branch_id' => $table->branch_id,
            'session_id' => $session->id,
            'order_no' => 'TEST-READY-CLOSE',
            'status' => 'sent',
            'subtotal' => 50000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 50000,
            'created_by' => $waiter->id,
        ]);
        DB::table('order_items')->insert([
            'order_id' => $order->id,
            'menu_item_id' => $menuItemId,
            'item_name_snapshot' => 'Test Ready Dish',
            'unit_price_snapshot' => 50000,
            'quantity' => 1,
            'line_total' => 50000,
            'status' => 'ready',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('payments')->insert([
            'branch_id' => $table->branch_id,
            'order_id' => $order->id,
            'method' => PaymentMethod::CASH->value,
            'amount' => 50000,
            'status' => PaymentStatus::SUCCESS->value,
            'confirmed_by' => $waiter->id,
            'paid_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->postJson("/api/tables/{$table->id}/dining-session/close", ['reason' => 'Khách rời đi'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(SessionStatus::CLOSED, $session->fresh()->status);
        $this->assertSame(TableStatus::CLEANING, $table->fresh()->status);
    }

    public function test_closing_a_session_is_blocked_when_order_has_items_not_sent_to_kitchen(): void
    {
        $table = $this->createTable(TableStatus::OCCUPIED);
        $waiter = $this->createWaiter($table->branch);
        $session = $this->createOpenSession($table, $waiter);
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Test Category',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $menuItemId = DB::table('menu_items')->insertGetId([
            'category_id' => $categoryId,
            'sku' => 'TEST-CLOSE-ORDER',
            'name' => 'Test Dish',
            'item_type' => 'dish',
            'station_code' => 'PHO',
            'base_price' => 50000,
            'tax_rate' => 8,
            'prep_time_seconds' => 300,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $order = Order::query()->create([
            'branch_id' => $table->branch_id,
            'session_id' => $session->id,
            'order_no' => 'TEST-CLOSE-1',
            'status' => 'draft',
            'subtotal' => 50000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 50000,
            'created_by' => $waiter->id,
        ]);
        DB::table('order_items')->insert([
            'order_id' => $order->id,
            'menu_item_id' => $menuItemId,
            'item_name_snapshot' => 'Test Dish',
            'unit_price_snapshot' => 50000,
            'quantity' => 1,
            'line_total' => 50000,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->postJson("/api/tables/{$table->id}/dining-session/close", ['reason' => 'Khách rời đi'])
            ->assertConflict()
            ->assertJsonPath('success', false);

        $this->assertSame(SessionStatus::OPEN, $session->fresh()->status);
        $this->assertSame(TableStatus::OCCUPIED, $table->fresh()->status);
    }

    public function test_marking_a_cleaning_table_cleaned_returns_it_to_available(): void
    {
        $table = $this->createTable(TableStatus::CLEANING);
        $waiter = $this->createWaiter($table->branch);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->postJson("/api/tables/{$table->id}/cleaned")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.table.status', TableStatus::AVAILABLE->value);

        $this->assertSame(TableStatus::AVAILABLE, $table->fresh()->status);
    }

    public function test_marking_one_merged_table_cleaned_cleans_all_tables_in_the_closed_session(): void
    {
        $source = $this->createTable(TableStatus::OCCUPIED);
        $target = $this->createSiblingTable($source, 'A02');
        $waiter = $this->createWaiter($source->branch);
        $session = $this->createOpenSession($source, $waiter);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $source->branch_id])
            ->postJson("/api/tables/{$source->id}/dining-session/merge", [
                'target_table_ids' => [$target->id],
            ])
            ->assertOk();

        $closedAt = now();
        SessionTable::query()
            ->where('session_id', $session->id)
            ->update(['left_at' => $closedAt]);
        $session->update(['status' => SessionStatus::CLOSED, 'closed_at' => $closedAt]);
        DiningTable::query()
            ->whereIn('id', [$source->id, $target->id])
            ->update(['status' => TableStatus::CLEANING]);
        AuditLog::query()->create([
            'branch_id' => $source->branch_id,
            'user_id' => $waiter->id,
            'action' => 'dining_session_close',
            'entity_type' => 'dining_session',
            'entity_id' => $session->id,
            'old_value' => [
                'status' => SessionStatus::OPEN->value,
                'table_ids' => [$source->id, $target->id],
            ],
            'new_value' => [
                'status' => SessionStatus::CLOSED->value,
                'table_status' => TableStatus::CLEANING->value,
            ],
            'created_at' => $closedAt,
        ]);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $source->branch_id])
            ->postJson("/api/tables/{$source->id}/cleaned")
            ->assertOk()
            ->assertJsonCount(2, 'data.table_ids');

        $this->assertSame(TableStatus::AVAILABLE, $source->fresh()->status);
        $this->assertSame(TableStatus::AVAILABLE, $target->fresh()->status);
    }

    public function test_closing_a_session_for_another_branch_returns_not_found(): void
    {
        $table = $this->createTable(TableStatus::OCCUPIED);
        $otherBranch = $this->createBranch();
        $waiter = $this->createWaiter($otherBranch);
        $session = $this->createOpenSession($table, $this->createWaiter($table->branch));

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $otherBranch->id])
            ->postJson("/api/tables/{$table->id}/dining-session/close", ['reason' => 'Khách rời đi'])
            ->assertNotFound();

        $this->assertDatabaseHas('dining_sessions', [
            'id' => $session->id,
            'status' => SessionStatus::OPEN->value,
        ]);
    }

    public function test_update_session_changes_guest_count_note_and_responsible_waiter(): void
    {
        $table = $this->createTable(TableStatus::OCCUPIED);
        $waiter = $this->createWaiter($table->branch);
        $session = $this->createOpenSession($table, $waiter);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->putJson("/api/dining-sessions/{$session->id}", [
                'guest_count' => 6,
                'note' => 'Sinh nhật · ghế trẻ em',
                'opened_by' => $waiter->id,
                'allow_overcapacity' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.guest_count', 6);

        $this->assertDatabaseHas('dining_sessions', [
            'id' => $session->id,
            'guest_count' => 6,
            'note' => 'Sinh nhật · ghế trẻ em',
            'opened_by' => $waiter->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'dining_session',
            'entity_id' => $session->id,
            'action' => 'dining_session_update',
        ]);
    }

    public function test_merge_tables_adds_available_tables_to_the_open_session(): void
    {
        $source = $this->createTable(TableStatus::OCCUPIED);
        $targetOne = $this->createSiblingTable($source, 'A02');
        $targetTwo = $this->createSiblingTable($source, 'A03');
        $waiter = $this->createWaiter($source->branch);
        $session = $this->createOpenSession($source, $waiter);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $source->branch_id])
            ->postJson("/api/tables/{$source->id}/dining-session/merge", [
                'target_table_ids' => [$targetOne->id, $targetTwo->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session_id', $session->id)
            ->assertJsonPath('data.total_capacity', 12);

        $this->assertDatabaseHas('session_tables', [
            'session_id' => $session->id,
            'table_id' => $targetOne->id,
            'left_at' => null,
        ]);
        $this->assertDatabaseHas('session_tables', [
            'session_id' => $session->id,
            'table_id' => $targetTwo->id,
            'left_at' => null,
        ]);
        $this->assertSame(TableStatus::OCCUPIED, $targetOne->fresh()->status);
        $this->assertSame(TableStatus::OCCUPIED, $targetTwo->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'dining_session',
            'entity_id' => $session->id,
            'action' => 'dining_session_merge',
        ]);
    }

    public function test_transfer_moves_open_session_and_its_orders_to_an_available_table_and_logs_the_actor(): void
    {
        $source = $this->createTable(TableStatus::OCCUPIED);
        $target = DiningTable::query()->create([
            'branch_id' => $source->branch_id,
            'area_id' => $source->area_id,
            'code' => 'A02',
            'seats' => 4,
            'shape' => TableShape::SQUARE,
            'status' => TableStatus::AVAILABLE,
            'is_active' => true,
        ]);
        $waiter = $this->createWaiter($source->branch);
        $session = $this->createOpenSession($source, $waiter);
        $order = Order::query()->create([
            'branch_id' => $source->branch_id,
            'session_id' => $session->id,
            'order_no' => 'TEST-ORDER-1',
            'created_by' => $waiter->id,
        ]);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $source->branch_id])
            ->postJson("/api/tables/{$source->id}/dining-session/transfer", [
                'target_table_id' => $target->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session_id', $session->id)
            ->assertJsonPath('data.source_table.id', $source->id)
            ->assertJsonPath('data.target_table.id', $target->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'session_id' => $session->id,
        ]);
        $this->assertDatabaseHas('session_tables', [
            'session_id' => $session->id,
            'table_id' => $source->id,
        ]);
        $this->assertNotNull(SessionTable::query()
            ->where('session_id', $session->id)
            ->where('table_id', $source->id)
            ->firstOrFail()
            ->left_at);
        $this->assertDatabaseHas('session_tables', [
            'session_id' => $session->id,
            'table_id' => $target->id,
            'left_at' => null,
        ]);
        $this->assertSame(TableStatus::AVAILABLE, $source->fresh()->status);
        $this->assertSame(TableStatus::OCCUPIED, $target->fresh()->status);

        $log = AuditLog::query()->where('entity_id', $session->id)->where('action', 'table_transfer')->firstOrFail();
        $this->assertSame($waiter->id, $log->user_id);
        $this->assertSame(['table_id' => $source->id, 'table_code' => $source->code], $log->old_value);
        $this->assertSame([
            'table_id' => $target->id,
            'table_code' => $target->code,
            'transferred_by' => $waiter->id,
        ], $log->new_value);
    }

    public function test_transfer_rejects_a_destination_that_is_not_available_without_changing_the_session(): void
    {
        $source = $this->createTable(TableStatus::OCCUPIED);
        $target = DiningTable::query()->create([
            'branch_id' => $source->branch_id,
            'area_id' => $source->area_id,
            'code' => 'A02',
            'seats' => 4,
            'shape' => TableShape::SQUARE,
            'status' => TableStatus::OCCUPIED,
            'is_active' => true,
        ]);
        $waiter = $this->createWaiter($source->branch);
        $session = $this->createOpenSession($source, $waiter);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $source->branch_id])
            ->postJson("/api/tables/{$source->id}/dining-session/transfer", [
                'target_table_id' => $target->id,
            ])
            ->assertConflict()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame(TableStatus::OCCUPIED, $source->fresh()->status);
        $this->assertSame(TableStatus::OCCUPIED, $target->fresh()->status);
        $this->assertDatabaseHas('session_tables', [
            'session_id' => $session->id,
            'table_id' => $source->id,
            'left_at' => null,
        ]);
    }

    public function test_transfer_rejects_a_destination_with_an_open_session_even_if_its_status_is_available(): void
    {
        $source = $this->createTable(TableStatus::OCCUPIED);
        $target = DiningTable::query()->create([
            'branch_id' => $source->branch_id,
            'area_id' => $source->area_id,
            'code' => 'A02',
            'seats' => 4,
            'shape' => TableShape::SQUARE,
            'status' => TableStatus::AVAILABLE,
            'is_active' => true,
        ]);
        $waiter = $this->createWaiter($source->branch);
        $sourceSession = $this->createOpenSession($source, $waiter);
        $targetSession = $this->createOpenSession($target, $waiter);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $source->branch_id])
            ->postJson("/api/tables/{$source->id}/dining-session/transfer", [
                'target_table_id' => $target->id,
            ])
            ->assertConflict()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertDatabaseHas('session_tables', [
            'session_id' => $sourceSession->id,
            'table_id' => $source->id,
            'left_at' => null,
        ]);
        $this->assertDatabaseHas('session_tables', [
            'session_id' => $targetSession->id,
            'table_id' => $target->id,
            'left_at' => null,
        ]);
    }

    public function test_transfer_rejects_the_same_table_as_destination(): void
    {
        $source = $this->createTable(TableStatus::OCCUPIED);
        $waiter = $this->createWaiter($source->branch);
        $this->createOpenSession($source, $waiter);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $source->branch_id])
            ->postJson("/api/tables/{$source->id}/dining-session/transfer", [
                'target_table_id' => $source->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['target_table_id']);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_selected_employee_must_be_an_active_waiter_for_the_current_branch(): void
    {
        $table = $this->createTable();
        $waiter = $this->createWaiter($table->branch);
        $otherBranchWaiter = $this->createWaiter($this->createBranch());

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->postJson('/api/dining-sessions', [
                'table_id' => $table->id,
                'guest_count' => 2,
                'opened_by' => $otherBranchWaiter->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['opened_by']);

        $this->assertDatabaseCount('dining_sessions', 0);
    }

    public function test_employee_endpoint_lists_only_active_waiters_for_the_current_branch(): void
    {
        $branch = $this->createBranch();
        $waiter = $this->createWaiter($branch);
        $this->createWaiter($this->createBranch());

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $branch->id])
            ->getJson('/api/dining-sessions/employees')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $waiter->id)
            ->assertJsonPath('data.0.full_name', $waiter->full_name);
    }

    public function test_current_session_endpoint_returns_session_table_and_employee_details(): void
    {
        $table = $this->createTable(TableStatus::OCCUPIED);
        $waiter = $this->createWaiter($table->branch);
        $session = $this->createOpenSession($table, $waiter);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->getJson("/api/tables/{$table->id}/dining-session")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $session->id)
            ->assertJsonPath('data.table.code', $table->code)
            ->assertJsonPath('data.guest_count', $session->guest_count)
            ->assertJsonPath('data.employee.id', $waiter->id)
            ->assertJsonPath('data.employee.full_name', $waiter->full_name);
    }

    public function test_current_session_endpoint_returns_not_found_when_no_session_is_open(): void
    {
        $table = $this->createTable();
        $waiter = $this->createWaiter($table->branch);

        $this->actingAs($waiter)
            ->withSession(['branch_id' => $table->branch_id])
            ->getJson("/api/tables/{$table->id}/dining-session")
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    private function createSiblingTable(DiningTable $source, string $code): DiningTable
    {
        return DiningTable::query()->create([
            'branch_id' => $source->branch_id,
            'area_id' => $source->area_id,
            'code' => $code,
            'seats' => 4,
            'shape' => TableShape::SQUARE,
            'status' => TableStatus::AVAILABLE,
            'is_active' => true,
        ]);
    }

    private function createTable(TableStatus $status = TableStatus::AVAILABLE): DiningTable
    {
        $branch = $this->createBranch();
        $area = Area::query()->create([
            'branch_id' => $branch->id,
            'code' => 'A',
            'name' => 'Khu A',
            'default_seats' => 4,
            'is_active' => true,
        ]);

        return DiningTable::query()->create([
            'branch_id' => $branch->id,
            'area_id' => $area->id,
            'code' => 'A01',
            'seats' => 4,
            'shape' => TableShape::SQUARE,
            'status' => $status,
            'is_active' => true,
        ]);
    }

    private function createBranch(): Branch
    {
        return Branch::query()->create([
            'code' => 'TEST'.str_pad((string) (Branch::query()->count() + 1), 3, '0', STR_PAD_LEFT),
            'name' => 'Test Branch',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => BranchStatus::ACTIVE,
        ]);
    }

    private function createWaiter(Branch $branch): User
    {
        $userNumber = User::query()->count() + 1;
        $user = User::query()->create([
            'username' => 'waiter'.$userNumber,
            'employee_code' => 'EMP'.$userNumber,
            'full_name' => 'Test Waiter '.$userNumber,
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
