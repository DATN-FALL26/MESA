<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BatchStatus;
use App\Enums\BranchStatus;
use App\Enums\ItemType;
use App\Enums\OrderChannel;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\ServeMode;
use App\Enums\StationCode;
use App\Enums\TicketStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use App\Models\Station;
use App\Services\Kitchen\KitchenTicketDispatcher;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class KitchenTicketDispatcherTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);

        $this->branch = Branch::query()->create([
            'code' => 'DSP01',
            'name' => 'Chi nhánh gửi bếp',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => BranchStatus::ACTIVE,
        ]);

        foreach ([StationCode::PHO, StationCode::DRINK] as $stationCode) {
            Station::query()->create([
                'branch_id' => $this->branch->id,
                'station_code' => $stationCode,
                'name' => 'Quầy '.$stationCode->value,
                'is_active' => true,
            ]);
        }
    }

    public function test_batch_items_are_split_into_one_ticket_per_station(): void
    {
        $batch = $this->createBatch();
        $pho = $this->addItem($batch, 'Phở bò tái', StationCode::PHO);
        $secondPho = $this->addItem($batch, 'Phở gà', StationCode::PHO);
        $drink = $this->addItem($batch, 'Trà đá', StationCode::DRINK);

        $tickets = app(KitchenTicketDispatcher::class)->sendBatch($batch);

        $this->assertCount(2, $tickets);
        $this->assertDatabaseCount('kitchen_tickets', 2);
        $this->assertDatabaseCount('kitchen_ticket_items', 3);

        $phoTicket = $tickets->first(fn ($ticket) => $ticket->station->station_code === StationCode::PHO);
        $this->assertSame(TicketStatus::NEW, $phoTicket->status);
        $this->assertEqualsCanonicalizing([$pho->id, $secondPho->id], $phoTicket->items()->pluck('order_item_id')->all());

        $this->assertSame(OrderItemStatus::SENT, $drink->fresh()->status);
        $this->assertSame(BatchStatus::SENT, $batch->fresh()->status);
        $this->assertNotNull($batch->fresh()->sent_at);
        $this->assertSame(OrderStatus::SENT, $batch->order->fresh()->status);
    }

    public function test_sending_again_only_creates_tickets_for_newly_added_items(): void
    {
        $batch = $this->createBatch();
        $this->addItem($batch, 'Phở bò tái', StationCode::PHO);
        $dispatcher = app(KitchenTicketDispatcher::class);

        $dispatcher->sendBatch($batch);
        $this->assertCount(0, $dispatcher->sendBatch($batch));
        $this->assertDatabaseCount('kitchen_ticket_items', 1);

        $addedLater = $this->addItem($batch, 'Trà đá', StationCode::DRINK);
        $tickets = $dispatcher->sendBatch($batch);

        $this->assertCount(1, $tickets);
        $this->assertSame([$addedLater->id], $tickets->first()->items()->pluck('order_item_id')->all());
        $this->assertDatabaseCount('kitchen_ticket_items', 2);
    }

    public function test_cancelled_items_are_not_sent_to_the_kitchen(): void
    {
        $batch = $this->createBatch();
        $this->addItem($batch, 'Phở bò tái', StationCode::PHO, OrderItemStatus::CANCELLED);

        $this->assertCount(0, app(KitchenTicketDispatcher::class)->sendBatch($batch));
        $this->assertDatabaseCount('kitchen_tickets', 0);
    }

    public function test_sending_fails_without_creating_tickets_when_a_station_is_missing(): void
    {
        $batch = $this->createBatch();
        $this->addItem($batch, 'Phở bò tái', StationCode::PHO);
        $this->addItem($batch, 'Quẩy', StationCode::SIDE);

        try {
            app(KitchenTicketDispatcher::class)->sendBatch($batch);
            $this->fail('Expected a validation exception for the missing SIDE station.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('SIDE', $exception->errors()['items'][0]);
        }

        $this->assertDatabaseCount('kitchen_tickets', 0);
    }

    public function test_send_order_sends_unsent_items_of_every_batch(): void
    {
        $firstBatch = $this->createBatch();
        $this->addItem($firstBatch, 'Phở bò tái', StationCode::PHO);
        $secondBatch = $this->createBatch($firstBatch->order, 2);
        $this->addItem($secondBatch, 'Trà đá', StationCode::DRINK);

        $tickets = app(KitchenTicketDispatcher::class)->sendOrder($firstBatch->order);

        $this->assertCount(2, $tickets);
        $this->assertEqualsCanonicalizing(
            [$firstBatch->id, $secondBatch->id],
            $tickets->pluck('batch_id')->all()
        );
    }

    private function createBatch(?Order $order = null, int $batchNo = 1): OrderBatch
    {
        $order ??= Order::query()->create([
            'branch_id' => $this->branch->id,
            'order_no' => 'T-'.Str::upper(Str::random(10)),
            'channel' => OrderChannel::TAKEAWAY,
            'status' => OrderStatus::DRAFT,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
        ]);

        return OrderBatch::query()->create([
            'order_id' => $order->id,
            'batch_no' => $batchNo,
            'serve_mode' => ServeMode::TOGETHER,
            'status' => BatchStatus::OPEN,
        ]);
    }

    private function addItem(OrderBatch $batch, string $name, StationCode $stationCode, OrderItemStatus $status = OrderItemStatus::PENDING): OrderItem
    {
        $category = Category::query()->create(['name' => 'Danh mục '.Str::random(6), 'is_active' => true]);
        $menuItem = MenuItem::query()->create([
            'category_id' => $category->id,
            'sku' => Str::upper(Str::random(8)),
            'name' => $name,
            'item_type' => ItemType::DISH,
            'station_code' => $stationCode,
            'base_price' => 40000,
            'tax_rate' => 0,
            'is_active' => true,
        ]);

        return OrderItem::query()->create([
            'order_id' => $batch->order_id,
            'batch_id' => $batch->id,
            'menu_item_id' => $menuItem->id,
            'item_name_snapshot' => $name,
            'unit_price_snapshot' => 40000,
            'quantity' => 1,
            'line_total' => 40000,
            'status' => $status,
        ]);
    }
}
