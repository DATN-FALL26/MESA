<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StationCode;
use App\Helpers\ConstantHelper;
use App\Models\Branch;
use App\Models\KitchenTicketItem;
use App\Models\User;
use App\Services\Payment\OrderPaymentService;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsKitchenOrders;
use Tests\TestCase;

/**
 * Phục vụ bấm +/− món đã gửi bếp: bếp và thanh toán chỉ nhận số lượng mới sau khi bấm "Cập nhật bếp".
 */
class KitchenQuantityConfirmTest extends TestCase
{
    use BuildsKitchenOrders;
    use RefreshDatabase;

    private Branch $branch;

    private User $waiter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
        $this->withoutVite();
        config(['payment.bank.account_no' => '']);
        $this->travelTo(today()->setTime(10, 0));

        $this->branch = $this->createBranch('QTY01');
        $this->waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER, $this->branch);
    }

    public function test_quantity_change_reaches_kitchen_and_payment_only_after_update_kitchen(): void
    {
        $order = $this->createSentOrder($this->branch, 'A03', [['Phở Bò Tái', StationCode::PHO]], $this->waiter);
        $orderItem = $order->items()->sole();
        $ticketItem = KitchenTicketItem::query()->where('order_item_id', $orderItem->id)->sole();
        $kitchen = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $this->branch);

        // Phục vụ bấm "+" trên món đã gửi bếp (bếp chưa bắt đầu nấu).
        $this->travel(1)->minutes();
        $this->actingAs($this->waiter)
            ->withSession(['branch_id' => $this->branch->id])
            ->post(route('orders.items.add', [$order->id, $orderItem->menu_item_id]));

        $this->assertSame(2, (int) $orderItem->fresh()->quantity);
        $this->assertSame(1, (int) $ticketItem->fresh()->sent_quantity);

        // Chưa "Cập nhật bếp": bếp vẫn thấy 1, thanh toán vẫn 50.000đ và có nhắc nhở.
        $this->actingAs($kitchen)
            ->get(route('kitchen.board'))
            ->assertSee('1x')
            ->assertDontSee('Đã sửa');

        $summary = app(OrderPaymentService::class)->summary($order->fresh());
        $this->assertSame(50_000, $summary['total']);
        $this->assertCount(1, $summary['unconfirmedItems']);

        $this->actingAs($this->waiter)
            ->get(route('payments.show', $order))
            ->assertSee('chưa bấm "Cập nhật bếp"', false)
            ->assertSeeInOrder(['Tổng cần thanh toán', '50.000đ']);

        // Bấm "Cập nhật bếp": bếp thấy 2 kèm "Đã sửa 1 → 2", thanh toán tính 100.000đ.
        $this->travel(1)->minutes();
        $this->actingAs($this->waiter)
            ->withSession(['branch_id' => $this->branch->id])
            ->post(route('orders.update-kitchen', $order->id));

        $this->assertSame(2, (int) $ticketItem->fresh()->sent_quantity);

        $this->actingAs($kitchen)
            ->get(route('kitchen.board'))
            ->assertSee('2x')
            ->assertSee('Đã sửa');

        $summary = app(OrderPaymentService::class)->summary($order->fresh());
        $this->assertSame(100_000, $summary['total']);
        $this->assertCount(0, $summary['unconfirmedItems']);
    }

    public function test_paid_order_only_asks_for_more_money_after_update_kitchen(): void
    {
        $order = $this->createSentOrder($this->branch, 'A04', [['Phở Bò Tái', StationCode::PHO]], $this->waiter);
        $orderItem = $order->items()->sole();
        $this->actingAs($this->waiter)->post(route('payments.cash.pay', $order), ['tendered_amount' => 50_000]);

        $this->travel(1)->minutes();
        $this->actingAs($this->waiter)
            ->withSession(['branch_id' => $this->branch->id])
            ->post(route('orders.items.add', [$order->id, $orderItem->menu_item_id]));

        $this->assertSame(0, app(OrderPaymentService::class)->summary($order->fresh())['remaining']);

        $this->travel(1)->minutes();
        $this->actingAs($this->waiter)
            ->withSession(['branch_id' => $this->branch->id])
            ->post(route('orders.update-kitchen', $order->id));

        $this->assertSame(50_000, app(OrderPaymentService::class)->summary($order->fresh())['remaining']);
    }

    public function test_branch_manager_cannot_access_orders_from_another_branch(): void
    {
        $otherBranch = $this->createBranch('QTY02');
        $otherBranchManager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER, $otherBranch);
        $order = $this->createSentOrder($this->branch, 'A05', [['Phở Bò Tái', StationCode::PHO]], $this->waiter);
        $orderItem = $order->items()->sole();

        $this->actingAs($otherBranchManager)
            ->withSession(['branch_id' => $otherBranch->id]);

        $requests = [
            fn () => $this->get(route('orders.session', $order->session_id)),
            fn () => $this->post(route('orders.start', $order->session_id)),
            fn () => $this->post(route('orders.items.add', [$order->id, $orderItem->menu_item_id])),
            fn () => $this->post(route('orders.items.decrease', [$order->id, $orderItem->id])),
            fn () => $this->post(route('orders.items.note', [$order->id, $orderItem->id])),
            fn () => $this->post(route('orders.send-kitchen', $order->id)),
            fn () => $this->post(route('orders.update-kitchen', $order->id)),
        ];

        foreach ($requests as $request) {
            $request()->assertNotFound();
        }

        $this->assertSame(1, (int) $orderItem->fresh()->quantity);
    }
}
