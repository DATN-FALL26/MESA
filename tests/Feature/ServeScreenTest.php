<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BatchStatus;
use App\Enums\NotificationType;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\StationCode;
use App\Helpers\ConstantHelper;
use App\Models\KitchenTicket;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsKitchenOrders;
use Tests\TestCase;

class ServeScreenTest extends TestCase
{
    use BuildsKitchenOrders;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
        $this->withoutVite();
    }

    public function test_waiter_is_notified_when_the_kitchen_finishes_a_table(): void
    {
        $branch = $this->createBranch('SRV01');
        $order = $this->createSentOrder($branch, 'B01', [['Phở bò tái', StationCode::PHO], ['Quẩy', StationCode::SIDE]]);
        $this->createSentOrder($branch, 'B03', [['Phở gà', StationCode::PHO]]);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);
        $waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER, $branch, 'Nhân viên phục vụ 02');

        $this->finishAllTicketsOf($order, $kitchenUser);

        $this->assertDatabaseHas('notifications', [
            'branch_id' => $branch->id,
            'target_role_code' => ConstantHelper::ROLE_WAITER,
            'type' => NotificationType::BATCH_ALL_READY->value,
            'title' => 'Bàn B01 đã xong món, mang ra bàn',
        ]);

        $this->actingAs($waiter)
            ->get(route('kitchen.serve.index'))
            ->assertOk()
            ->assertSee('Nhân viên phục vụ 02')
            ->assertSee('Bàn B01 vừa xong món, mang ra bàn')
            ->assertSeeInOrder(['Món chờ phục vụ', 'Bàn B01', 'Phở bò tái', 'Quẩy', 'Đã phục vụ', 'Đang ở bếp', 'Bàn B03', 'Mới']);
    }

    public function test_waiter_confirms_served_and_order_batch_and_kds_are_updated(): void
    {
        $branch = $this->createBranch('SRV01');
        $order = $this->createSentOrder($branch, 'B01', [['Phở bò tái', StationCode::PHO]]);
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $branch);
        $waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER, $branch);

        $this->finishAllTicketsOf($order, $kitchenUser);

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertSee('đang chờ mang món ra bàn');

        $this->actingAs($waiter)
            ->postJson(route('kitchen.serve.served', $order))
            ->assertOk();

        $this->assertSame(OrderItemStatus::SERVED, $order->items()->first()->status);
        $this->assertSame(BatchStatus::SERVED, $order->batches()->first()->status);
        $this->assertNotNull($order->batches()->first()->served_at);
        $this->assertSame(OrderStatus::SERVED, $order->fresh()->status);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'from_status' => 'ready',
            'to_status' => 'served',
            'changed_by' => $waiter->id,
        ]);

        $this->actingAs($waiter)
            ->get(route('kitchen.serve.board'))
            ->assertOk()
            ->assertSee('Chưa có món nào chờ mang ra');

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.board'))
            ->assertSee('Đã phục vụ lúc');

        $this->actingAs($waiter)
            ->postJson(route('kitchen.serve.served', $order))
            ->assertUnprocessable();
    }

    public function test_waiter_cannot_serve_an_order_of_another_branch(): void
    {
        $order = $this->createSentOrder($this->createBranch('SRV02'), 'C09', [['Phở gà', StationCode::PHO]]);
        $waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER, $this->createBranch('SRV01'));

        $this->actingAs($waiter)
            ->postJson(route('kitchen.serve.served', $order))
            ->assertForbidden();
    }

    public function test_kitchen_staff_without_serve_permission_cannot_open_the_serve_screen(): void
    {
        $kitchenUser = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $this->createBranch('SRV01'));

        $this->actingAs($kitchenUser)
            ->get(route('kitchen.serve.index'))
            ->assertForbidden();
    }

    private function finishAllTicketsOf(Order $order, User $kitchenUser): void
    {
        KitchenTicket::query()->where('order_id', $order->id)->each(function (KitchenTicket $ticket) use ($kitchenUser): void {
            $this->actingAs($kitchenUser)->patchJson(route('kitchen.tickets.advance', $ticket))->assertOk();
            $this->actingAs($kitchenUser)->patchJson(route('kitchen.tickets.advance', $ticket))->assertOk();
        });
    }
}
