<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BatchStatus;
use App\Enums\OrderItemStatus;
use App\Enums\StationCode;
use App\Enums\TicketItemStatus;
use App\Enums\TicketStatus;
use App\Helpers\ConstantHelper;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsKitchenOrders;
use Tests\TestCase;

class KitchenDisplayTest extends TestCase
{
    use BuildsKitchenOrders;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
        $this->withoutVite();
    }

    public function test_kitchen_staff_sees_active_tickets_of_their_branch_with_table_item_and_note(): void
    {
        $branch = $this->createBranch('KDS01');
        $ticket = $this->createTicket($branch, 'B01', 'Phở bò tái', StationCode::PHO, 'Không hành');
        $otherBranchTicket = $this->createTicket($this->createBranch('KDS02'), 'C09', 'Phở gà chi nhánh khác', StationCode::PHO);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.index'))
            ->assertOk()
            ->assertSee('Bàn B01')
            ->assertSee('Phở bò tái')
            ->assertSee('Không hành')
            ->assertSee('data-ticket-id="'.$ticket->id.'"', false)
            ->assertSee('Bắt đầu nấu')
            ->assertDontSee('Phở gà chi nhánh khác')
            ->assertDontSee('data-ticket-id="'.$otherBranchTicket->id.'"', false);
    }

    public function test_board_can_be_filtered_by_station(): void
    {
        $branch = $this->createBranch('KDS01');
        $this->createTicket($branch, 'B01', 'Phở bò tái', StationCode::PHO);
        $this->createTicket($branch, 'B02', 'Trà đá', StationCode::DRINK);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board', ['station' => StationCode::DRINK->value]))
            ->assertOk()
            ->assertSee('Trà đá')
            ->assertDontSee('Phở bò tái');
    }

    public function test_user_without_kitchen_permission_cannot_open_kds(): void
    {
        $warehouseStaff = $this->createUserWithRole(ConstantHelper::ROLE_WAREHOUSE);

        $this->actingAs($warehouseStaff)
            ->get(route('kitchen.index'))
            ->assertForbidden();
    }

    public function test_kitchen_staff_advances_an_item_from_new_to_preparing_to_ready_and_it_is_logged(): void
    {
        $branch = $this->createBranch('KDS01');
        $ticket = $this->createTicket($branch, 'B01', 'Phở bò tái', StationCode::PHO);
        $ticketItem = $ticket->items()->firstOrFail();
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->actingAs($kitchenUser)
            ->patchJson(route('kitchen.ticket-items.advance', $ticketItem))
            ->assertOk();

        $this->assertSame(TicketItemStatus::PREPARING, $ticketItem->fresh()->status);
        $this->assertNotNull($ticketItem->fresh()->started_at);
        $this->assertSame(OrderItemStatus::PREPARING, $ticketItem->orderItem->fresh()->status);
        $this->assertSame(TicketStatus::PREPARING, $ticket->fresh()->status);
        $this->assertSame(BatchStatus::COOKING, $ticket->batch->fresh()->status);

        $this->actingAs($kitchenUser)
            ->patchJson(route('kitchen.ticket-items.advance', $ticketItem))
            ->assertOk();

        $this->assertSame(TicketItemStatus::READY, $ticketItem->fresh()->status);
        $this->assertNotNull($ticketItem->fresh()->ready_at);
        $this->assertSame(OrderItemStatus::READY, $ticketItem->orderItem->fresh()->status);
        $this->assertSame(TicketStatus::READY, $ticket->fresh()->status);
        $this->assertSame($kitchenUser->id, $ticket->fresh()->updated_by);
        $this->assertSame(BatchStatus::ALL_READY, $ticket->batch->fresh()->status);

        $this->assertDatabaseHas('order_status_history', [
            'order_item_id' => $ticketItem->order_item_id,
            'from_status' => 'new',
            'to_status' => 'preparing',
            'changed_by' => $kitchenUser->id,
        ]);
        $this->assertDatabaseHas('order_status_history', [
            'order_item_id' => $ticketItem->order_item_id,
            'from_status' => 'preparing',
            'to_status' => 'ready',
            'changed_by' => $kitchenUser->id,
        ]);

        $this->actingAs($kitchenUser)
            ->patchJson(route('kitchen.ticket-items.advance', $ticketItem))
            ->assertUnprocessable();
    }

    public function test_ticket_moves_through_columns_and_leaves_done_column_after_thirty_minutes(): void
    {
        $branch = $this->createBranch('KDS01');
        $ticket = $this->createTicket($branch, 'B01', 'Phở bò tái', StationCode::PHO);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->actingAs($kitchenUser)->patchJson(route('kitchen.tickets.advance', $ticket))->assertOk();
        $this->assertSame(TicketStatus::PREPARING, $ticket->fresh()->status);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertOk()
            ->assertSeeInOrder(['Đang nấu', 'Phở bò tái', 'Hoàn thành']);

        $this->actingAs($kitchenUser)->patchJson(route('kitchen.tickets.advance', $ticket))->assertOk();
        $this->assertSame(TicketStatus::READY, $ticket->fresh()->status);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertOk()
            ->assertSeeInOrder(['Hoàn thành', 'Phở bò tái', 'Đã báo phục vụ – đang chờ mang món ra bàn']);

        $this->travel(31)->minutes();

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertOk()
            ->assertDontSee('Phở bò tái');
    }

    public function test_kitchen_staff_cannot_update_a_ticket_of_another_branch(): void
    {
        $ticket = $this->createTicket($this->createBranch('KDS02'), 'C09', 'Phở gà', StationCode::PHO);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $this->createBranch('KDS01'));

        $this->actingAs($kitchenUser)
            ->patchJson(route('kitchen.tickets.advance', $ticket))
            ->assertForbidden();

        $this->assertSame(TicketStatus::NEW, $ticket->fresh()->status);
    }
}
