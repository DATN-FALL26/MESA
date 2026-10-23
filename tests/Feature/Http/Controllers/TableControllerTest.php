<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\DiningSession;
use App\Models\Table;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TableControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_table_map_renders_saved_tables(): void
    {
        $this->createTable('Table 1', 'North');

        $this->get('/tables')
            ->assertOk()
            ->assertViewIs('tables.index')
            ->assertSeeText('Table 1')
            ->assertSeeText('Thêm bàn')
            ->assertSee('aria-label="Sửa Table 1"', false);
    }

    public function test_status_endpoint_returns_tables_for_the_requested_zone(): void
    {
        $this->createTable('North Table', 'North');
        $this->createTable('South Table', 'South');

        $this->getJson('/api/tables/status?zone=North')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'North Table');
    }

    public function test_valid_payload_creates_a_table_and_returns_201(): void
    {
        $this->postJson('/api/tables', [
            'name' => 'Table 3',
            'zone' => 'North',
            'capacity' => 6,
            'status' => 'empty',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Table 3');

        $this->assertDatabaseHas('tables', [
            'name' => 'Table 3',
            'zone' => 'North',
            'capacity' => 6,
            'status' => 'empty',
        ]);
    }

    public function test_update_changes_table_details(): void
    {
        $table = $this->createTable('Table 1', 'North');

        $this->putJson("/api/tables/{$table->id}", [
            'name' => 'Table 1A',
            'zone' => 'South',
            'status' => 'cleaning',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Table 1A')
            ->assertJsonPath('data.status', 'cleaning');

        $this->assertDatabaseHas('tables', [
            'id' => $table->id,
            'name' => 'Table 1A',
            'zone' => 'South',
            'status' => 'cleaning',
        ]);
    }

    public function test_delete_removes_the_table(): void
    {
        $table = $this->createTable('Table 1', 'North');

        $this->deleteJson("/api/tables/{$table->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('tables', ['id' => $table->id]);
    }

    public function test_delete_returns_conflict_for_an_occupied_table(): void
    {
        $table = $this->createTable('Table 1', 'North');
        $table->update(['status' => 'occupied']);

        $this->deleteJson("/api/tables/{$table->id}")
            ->assertConflict()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('tables', ['id' => $table->id]);
    }

    public function test_delete_returns_conflict_for_a_table_with_an_open_session(): void
    {
        $table = $this->createTable('Table 1', 'North');
        DiningSession::create([
            'table_id' => $table->id,
            'customer_count' => 2,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $this->deleteJson("/api/tables/{$table->id}")
            ->assertConflict()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('tables', ['id' => $table->id]);
        $this->assertDatabaseHas('dining_sessions', [
            'table_id' => $table->id,
            'status' => 'open',
        ]);
    }

    public function test_invalid_table_payload_returns_validation_errors(): void
    {
        $this->postJson('/api/tables', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'zone']);
    }

    public function test_table_payload_rejects_invalid_capacity_and_status(): void
    {
        $this->postJson('/api/tables', [
            'name' => 'Table 3',
            'zone' => 'North',
            'capacity' => 0,
            'status' => 'reserved',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['capacity', 'status']);
    }

    private function createTable(string $name, string $zone): Table
    {
        return Table::create([
            'name' => $name,
            'zone' => $zone,
            'capacity' => 4,
            'status' => 'empty',
        ]);
    }
}
