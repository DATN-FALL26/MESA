<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BatchStatus;
use App\Enums\OrderItemStatus;
use App\Enums\StationCode;
use App\Enums\TicketItemStatus;
use App\Enums\TicketStatus;
use App\Helpers\ConstantHelper;
use App\Models\KitchenTicketItem;
use App\Models\PrintJob;
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

    public function test_header_counts_only_pho_bowls_still_to_cook_and_cooked_today(): void
    {
        $branch = $this->createBranch('KDS01');
        $phoTicket = $this->createTicket($branch, 'B01', 'Phở bò tái', StationCode::PHO);
        $this->createSentOrder($branch, 'B02', [
            ['Phở gà', StationCode::PHO],
            ['Phở chín', StationCode::PHO],
            ['Trà đá', StationCode::DRINK],
        ]);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.index'))
            ->assertOk()
            ->assertDontSee('Máy in nhiệt')
            ->assertSee('data-pending="3" data-done="0"', false);

        $this->actingAs($kitchenUser)->patchJson(route('kitchen.tickets.advance', $phoTicket))->assertOk();
        $this->actingAs($kitchenUser)->patchJson(route('kitchen.tickets.advance', $phoTicket))->assertOk();

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board', ['station' => StationCode::SIDE->value]))
            ->assertOk()
            ->assertSee('data-pending="2" data-done="1"', false);
    }

    public function test_kds_flags_quantity_changed_after_sending_until_the_kitchen_starts_cooking(): void
    {
        $branch = $this->createBranch('KDS01');
        $ticket = $this->createTicket($branch, 'B01', 'Phở bò chín', StationCode::PHO);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        // Phục vụ tăng số lượng món đã gửi bếp (giống màn gọi món của MOD03).
        $ticket->items()->first()->orderItem->update(['quantity' => 3]);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertOk()
            ->assertSee('3x')
            ->assertSee('Đã sửa: 1 → 3')
            ->assertSee('kds-card-changed', false);

        $this->actingAs($kitchenUser)->patchJson(route('kitchen.tickets.advance', $ticket))->assertOk();

        $this->assertSame(3, $ticket->items()->first()->notified_quantity);
        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertDontSee('Đã sửa');
    }

    public function test_kds_shows_cancelled_items_and_hides_fully_cancelled_tickets(): void
    {
        $branch = $this->createBranch('KDS01');
        $order = $this->createSentOrder($branch, 'B01', [['Phở bò tái', StationCode::PHO], ['Phở gà', StationCode::PHO]]);
        $emptiedTicket = $this->createTicket($branch, 'B02', 'Phở chín bị hủy', StationCode::PHO);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->cancelTicketItem($order->kitchenTickets()->first()->items()->latest('id')->first());
        $this->cancelTicketItem($emptiedTicket->items()->first());

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertOk()
            ->assertSee('Phở bò tái')
            ->assertSeeInOrder(['Phở gà', 'Đã hủy'])
            ->assertDontSee('data-ticket-id="'.$emptiedTicket->id.'"', false)
            ->assertSee('data-pending="1" data-done="0"', false);
    }

    public function test_ready_made_ticket_changed_after_done_returns_to_its_column_and_reprints(): void
    {
        $branch = $this->createBranch('KDS01');
        $this->createStation($branch, StationCode::PHO, printerIsActive: true);
        $order = $this->createSentOrder($branch, 'B05', [['Trà đá', StationCode::DRINK]]);
        $ticket = $order->kitchenTickets()->firstOrFail();
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->actingAs($kitchenUser)->patchJson(route('kitchen.tickets.advance', $ticket))->assertOk();
        $this->assertSame(TicketStatus::READY, $ticket->fresh()->status);
        $this->assertSame(1, PrintJob::query()->where('ref_id', $ticket->id)->count());

        // Khách gọi thêm trà đá vào cùng món đã xong.
        $ticket->items()->first()->orderItem->update(['quantity' => 2]);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertSeeInOrder(['Nước &amp; Quẩy', 'Trà đá', 'Đã sửa 1→2', 'Xong', 'Hoàn thành'], false);

        $this->actingAs($kitchenUser)->patchJson(route('kitchen.tickets.advance', $ticket))->assertOk();

        $this->assertSame(2, $ticket->items()->first()->notified_quantity);
        $this->assertSame(2, PrintJob::query()->where('ref_id', $ticket->id)->count());
        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertDontSee('Đã sửa');
    }

    public function test_drinks_and_sides_go_to_their_own_column_and_finish_with_one_tap(): void
    {
        $branch = $this->createBranch('KDS01');
        $order = $this->createSentOrder($branch, 'B05', [['Phở bò tái', StationCode::PHO], ['Trà đá', StationCode::DRINK]]);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertOk()
            ->assertSeeInOrder(['Mới', 'Phở bò tái', 'Đang nấu', 'Nước &amp; Quẩy', 'Trà đá', 'Xong', 'Hoàn thành'], false);

        $drinkTicket = $order->kitchenTickets()->whereHas('station', fn ($query) => $query->where('station_code', StationCode::DRINK))->firstOrFail();

        $this->actingAs($kitchenUser)->patchJson(route('kitchen.tickets.advance', $drinkTicket))->assertOk();

        $this->assertSame(TicketStatus::READY, $drinkTicket->fresh()->status);
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

    public function test_done_ticket_stays_until_served_then_hides_one_minute_later(): void
    {
        // Cố định giờ ban ngày để việc tua thời gian không làm sang ngày mới.
        $this->travelTo(today()->setTime(10, 0));

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
            ->assertSee('Phở bò tái');

        $waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER, $branch);
        $this->actingAs($waiter)->postJson(route('kitchen.serve.served', $ticket->order))->assertOk();

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertOk()
            ->assertSeeInOrder(['Hoàn thành', 'Phở bò tái', 'Đã phục vụ lúc']);

        $this->travel(61)->seconds();

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

    /**
     * Phục vụ hủy món đã gửi bếp (giống MOD03: hủy dòng món và dòng trên phiếu bếp).
     */
    private function cancelTicketItem(KitchenTicketItem $ticketItem): void
    {
        $ticketItem->orderItem->update(['status' => OrderItemStatus::CANCELLED, 'cancelled_at' => now()]);
        $ticketItem->update(['status' => TicketItemStatus::CANCELLED]);
    }
}
