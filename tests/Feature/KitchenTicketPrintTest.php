<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PrintDocType;
use App\Enums\PrintJobStatus;
use App\Enums\StationCode;
use App\Helpers\ConstantHelper;
use App\Models\KitchenTicket;
use App\Models\PrintJob;
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

    public function test_ticket_is_printed_automatically_when_the_order_is_sent_to_the_kitchen(): void
    {
        $branch = $this->createBranch('PRN01');
        $this->createStation($branch, StationCode::PHO, printerIsActive: true);
        $ticket = $this->createTicket($branch, 'B07', 'Phở bò tái', StationCode::PHO);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $printJob = PrintJob::query()->where('ref_id', $ticket->id)->sole();
        $this->assertSame(PrintDocType::KITCHEN_TICKET, $printJob->doc_type);
        $this->assertSame(PrintJobStatus::PRINTED, $printJob->status);
        $this->assertNotNull($printJob->printed_at);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.index'))
            ->assertOk()
            ->assertSee('Đã in tự động')
            ->assertDontSee('phiếu chưa in');
    }

    public function test_kds_reports_an_error_when_the_printer_is_offline(): void
    {
        $branch = $this->createBranch('PRN01');
        $this->createStation($branch, StationCode::PHO, printerIsActive: false);
        $ticket = $this->createTicket($branch, 'B07', 'Phở bò tái', StationCode::PHO);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->assertSame(PrintJobStatus::FAILED, PrintJob::query()->where('ref_id', $ticket->id)->sole()->status);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.index'))
            ->assertOk()
            ->assertSee('Máy in lỗi')
            ->assertSee('1 phiếu chưa in')
            ->assertSee('In lại phiếu');
    }

    public function test_kds_reports_an_error_when_the_station_has_no_printer(): void
    {
        $branch = $this->createBranch('PRN01');
        $this->createTicket($branch, 'B07', 'Phở bò tái', StationCode::PHO);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->assertDatabaseCount('print_jobs', 0);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertOk()
            ->assertSee('Quầy chưa gắn máy in');
    }

    public function test_kitchen_staff_can_reprint_a_ticket(): void
    {
        $branch = $this->createBranch('PRN01');
        $this->createStation($branch, StationCode::PHO, printerIsActive: true);
        $ticket = $this->createTicket($branch, 'B07', 'Phở bò tái', StationCode::PHO, 'ít hành');
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);

        $this->actingAs($kitchenUser)
            ->post(route('kitchen.tickets.print', $ticket))
            ->assertOk()
            ->assertSee('PHIẾU BẾP')
            ->assertSee('BÀN B07')
            ->assertSee('1x Phở bò tái')
            ->assertSee('Ghi chú: ít hành')
            ->assertSee('In lại lần 1');

        $this->assertSame(2, PrintJob::query()->where('ref_id', $ticket->id)->count());
        $this->assertSame(1, PrintJob::query()->where('ref_id', $ticket->id)->latest('id')->first()->retry_count);
    }

    public function test_user_without_print_permission_cannot_reprint(): void
    {
        $branch = $this->createBranch('PRN01');
        $ticket = $this->createTicket($branch, 'B07', 'Phở bò tái', StationCode::PHO);
        $waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER, $branch);

        $this->actingAs($waiter)
            ->post(route('kitchen.tickets.print', $ticket))
            ->assertForbidden();

        $this->assertInstanceOf(KitchenTicket::class, $ticket->fresh());
    }
}
