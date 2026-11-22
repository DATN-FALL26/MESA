<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PrintDocType;
use App\Enums\PrintJobStatus;
use App\Enums\StationCode;
use App\Enums\TicketStatus;
use App\Helpers\ConstantHelper;
use App\Models\KitchenTicket;
use App\Models\PrintJob;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsKitchenOrders;
use Tests\TestCase;

class KitchenTicketPrintTest extends TestCase
{
    use BuildsKitchenOrders;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
        $this->withoutVite();
    }

    public function test_slip_is_printed_only_after_the_kitchen_completes_the_ticket(): void
    {
        $branch = $this->createBranch('PRN01');
        $this->createStation($branch, StationCode::PHO, printerIsActive: true);
        $ticket = $this->createTicket($branch, 'D05', 'Phở bò tái', StationCode::PHO);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->assertDatabaseCount('print_jobs', 0);

        $this->actingAs($kitchenUser)->patchJson(route('kitchen.tickets.advance', $ticket))->assertOk();
        $this->assertDatabaseCount('print_jobs', 0);

        $this->completeTicket($ticket, $kitchenUser, startCooking: false);

        $printJob = PrintJob::query()->where('ref_id', $ticket->id)->sole();
        $this->assertSame(PrintDocType::KITCHEN_TICKET, $printJob->doc_type);
        $this->assertSame(PrintJobStatus::PRINTED, $printJob->status);
        $this->assertNotNull($printJob->printed_at);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.index'))
            ->assertOk()
            ->assertSeeInOrder(['Hoàn thành', 'Phở bò tái', 'Đã in tự động'])
            ->assertDontSee('Máy in nhiệt');
    }

    public function test_kds_reports_an_error_when_the_printer_is_offline(): void
    {
        $branch = $this->createBranch('PRN01');
        $this->createStation($branch, StationCode::PHO, printerIsActive: false);
        $ticket = $this->createTicket($branch, 'D05', 'Phở bò tái', StationCode::PHO);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->completeTicket($ticket, $kitchenUser);

        $this->assertSame(PrintJobStatus::FAILED, PrintJob::query()->where('ref_id', $ticket->id)->sole()->status);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.index'))
            ->assertOk()
            ->assertSee('Máy in lỗi')
            ->assertSee('In lại phiếu');
    }

    public function test_kds_reports_an_error_when_the_branch_has_no_kitchen_printer(): void
    {
        $branch = $this->createBranch('PRN01');
        $ticket = $this->createTicket($branch, 'D05', 'Phở bò tái', StationCode::PHO);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->completeTicket($ticket, $kitchenUser);

        $this->assertDatabaseCount('print_jobs', 0);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertOk()
            ->assertSee('chi nhánh chưa có máy in bếp');
    }

    public function test_drinks_and_sides_are_printed_on_the_branch_kitchen_printer_after_one_tap(): void
    {
        $branch = $this->createBranch('PRN01');
        $phoStation = $this->createStation($branch, StationCode::PHO, printerIsActive: true);
        $order = $this->createSentOrder($branch, 'D05', [['Trà đá', StationCode::DRINK], ['Quẩy', StationCode::SIDE]]);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $tickets = KitchenTicket::query()->where('order_id', $order->id)->get();
        $this->assertCount(1, $tickets);
        $this->assertCount(2, $tickets->first()->items);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertOk()
            ->assertSeeInOrder(['Đang nấu', 'Nước & Quẩy', 'Trà đá', 'Xong', 'Hoàn thành']);

        foreach ($tickets as $ticket) {
            $this->assertSame(TicketStatus::PREPARING, $ticket->status);
            $this->actingAs($kitchenUser)->patchJson(route('kitchen.tickets.advance', $ticket))->assertOk();
            $this->assertSame(TicketStatus::READY, $ticket->fresh()->status);

            $printJob = PrintJob::query()->where('ref_id', $ticket->id)->sole();
            $this->assertSame($phoStation->printer_id, $printJob->printer_id);
            $this->assertSame(PrintJobStatus::PRINTED, $printJob->status);
        }
    }

    public function test_completion_slip_shows_table_order_staff_times_and_items(): void
    {
        $branch = $this->createBranch('PRN01');
        $this->createStation($branch, StationCode::PHO, printerIsActive: true);
        $ticket = $this->createTicket($branch, 'D05', 'Sốt vang', StationCode::PHO, 'ít hành');
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->completeTicket($ticket, $kitchenUser);
        $ticket->refresh();

        $this->actingAs($kitchenUser)
            ->post(route('kitchen.tickets.print', $ticket))
            ->assertOk()
            ->assertSee('PHỞ D05')
            ->assertSee('Mã ĐH: '.$ticket->order->created_at->format('Ymd').'-'.str_pad((string) $ticket->order_id, 4, '0', STR_PAD_LEFT))
            ->assertSee('Bắt đầu:')
            ->assertSee($ticket->fired_at->format('H:i d/m/Y'))
            ->assertSee('Trả món:')
            ->assertSee($ticket->ready_at->format('H:i d/m/Y'))
            ->assertSee('ĐÃ XONG')
            ->assertSeeInOrder(['Tên sản phẩm', 'SL', 'Sốt vang', 'Ghi chú: ít hành'])
            ->assertDontSee('PHIẾU BẾP');
    }

    public function test_kitchen_staff_can_reprint_a_completed_slip(): void
    {
        $branch = $this->createBranch('PRN01');
        $this->createStation($branch, StationCode::PHO, printerIsActive: true);
        $ticket = $this->createTicket($branch, 'D05', 'Phở bò tái', StationCode::PHO);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->completeTicket($ticket, $kitchenUser);

        $this->actingAs($kitchenUser)
            ->post(route('kitchen.tickets.print', $ticket))
            ->assertOk()
            ->assertSee('In lại lần 1');

        $this->assertSame(2, PrintJob::query()->where('ref_id', $ticket->id)->count());
    }

    public function test_slip_cannot_be_printed_before_the_ticket_is_completed(): void
    {
        $branch = $this->createBranch('PRN01');
        $ticket = $this->createTicket($branch, 'D05', 'Phở bò tái', StationCode::PHO);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->actingAs($kitchenUser)
            ->post(route('kitchen.tickets.print', $ticket))
            ->assertStatus(422);

        $this->assertDatabaseCount('print_jobs', 0);
    }

    public function test_user_without_print_permission_cannot_print(): void
    {
        $branch = $this->createBranch('PRN01');
        $ticket = $this->createTicket($branch, 'D05', 'Phở bò tái', StationCode::PHO);
        $waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER, $branch);

        $this->actingAs($waiter)
            ->post(route('kitchen.tickets.print', $ticket))
            ->assertForbidden();
    }

    private function completeTicket(KitchenTicket $ticket, User $kitchenUser, bool $startCooking = true): void
    {
        if ($startCooking) {
            $this->actingAs($kitchenUser)->patchJson(route('kitchen.tickets.advance', $ticket))->assertOk();
        }

        $this->actingAs($kitchenUser)->patchJson(route('kitchen.tickets.advance', $ticket))->assertOk();
    }
}
