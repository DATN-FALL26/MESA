<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StationCode;
use App\Helpers\ConstantHelper;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\OrderPaymentService;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsKitchenOrders;
use Tests\TestCase;

/**
 * Thanh toán tiền mặt: khách đưa thiếu thì phần còn lại trả QR / chuyển khoản.
 */
class PaymentMixedTest extends TestCase
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

        $this->branch = $this->createBranch('MIX01');
        $this->waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER, $this->branch);
    }

    public function test_payment_page_only_offers_cash_and_qr(): void
    {
        $order = $this->createSentOrder($this->branch, 'A04', [['Phở Bò Tái', StationCode::PHO]], $this->waiter);

        $this->actingAs($this->waiter)
            ->get(route('payments.show', $order))
            ->assertSee('Tiền mặt')
            ->assertSee('QR / chuyển khoản')
            ->assertDontSee('Kết hợp');

        $this->actingAs($this->waiter)
            ->get(route('payments.cash', $order))
            ->assertSee('Khách đưa thiếu thì phần còn lại sẽ tạo mã QR.');
    }

    public function test_short_cash_is_recorded_then_qr_for_the_rest(): void
    {
        $order = $this->createSentOrder($this->branch, 'A05', [['Phở Bò Tái', StationCode::PHO]], $this->waiter);

        // Khách đưa 30.000đ tiền mặt: ghi nhận 30.000đ, tự tạo mã QR 20.000đ cho phần còn lại.
        $response = $this->actingAs($this->waiter)
            ->post(route('payments.cash.pay', $order), ['tendered_amount' => 30_000]);

        $cash = Payment::query()->where('order_id', $order->id)->where('method', PaymentMethod::CASH)->sole();
        $this->assertSame(30_000, (int) $cash->amount);
        $this->assertSame(0, (int) $cash->change_amount);
        $this->assertSame(PaymentStatus::SUCCESS, $cash->status);

        $qr = Payment::query()->where('order_id', $order->id)->where('method', PaymentMethod::QR)->sole();
        $this->assertSame(20_000, (int) $qr->amount);
        $this->assertSame(PaymentStatus::PENDING, $qr->status);
        $response->assertRedirect(route('payments.qr', $qr));
        $this->assertSame(20_000, app(OrderPaymentService::class)->summary($order->fresh())['remaining']);

        // Khách chuyển khoản xong: order đã thanh toán đủ.
        $this->actingAs($this->waiter)->post(route('payments.qr.confirm', $qr));

        $summary = app(OrderPaymentService::class)->summary($order->fresh());
        $this->assertSame(50_000, $summary['paid']);
        $this->assertSame(0, $summary['remaining']);
    }

    public function test_enough_cash_gives_change(): void
    {
        $order = $this->createSentOrder($this->branch, 'A06', [['Phở Bò Tái', StationCode::PHO]], $this->waiter);

        $this->actingAs($this->waiter)
            ->post(route('payments.cash.pay', $order), ['tendered_amount' => 100_000])
            ->assertRedirect(route('payments.show', $order));

        $cash = Payment::query()->where('order_id', $order->id)->sole();
        $this->assertSame(50_000, (int) $cash->amount);
        $this->assertSame(50_000, (int) $cash->change_amount);
    }

    public function test_cash_requires_some_amount(): void
    {
        $order = $this->createSentOrder($this->branch, 'A07', [['Phở Bò Tái', StationCode::PHO]], $this->waiter);

        $this->actingAs($this->waiter)
            ->post(route('payments.cash.pay', $order), ['tendered_amount' => 0])
            ->assertSessionHasErrors('tendered_amount');

        $this->assertSame(0, Payment::query()->where('order_id', $order->id)->count());
    }
}
