<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderItemStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StationCode;
use App\Helpers\ConstantHelper;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\VietQrPayload;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsKitchenOrders;
use Tests\TestCase;

class PaymentTest extends TestCase
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

        $this->branch = $this->createBranch('PAY01');
        $this->waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER, $this->branch);
    }

    public function test_payment_page_shows_the_bill_and_the_amount_to_pay(): void
    {
        $order = $this->createSentOrder($this->branch, 'B05', [['Phở bò tái', StationCode::PHO], ['Trà đá', StationCode::DRINK]]);

        $this->actingAs($this->waiter)
            ->get(route('payments.show', $order))
            ->assertOk()
            ->assertSee('Hóa đơn tạm tính')
            ->assertSeeInOrder(['Phở bò tái', 'Trà đá', 'Tổng cộng', '100.000đ'])
            ->assertSee('Tổng cần thanh toán')
            ->assertSee('Còn 2 món chưa phục vụ')
            ->assertSee('Tiếp tục thanh toán')
            ->assertDontSee('I-Pho · Thu ngân')
            ->assertDontSee('Hóa đơn</li>', false);
    }

    public function test_waiter_collects_cash_and_change_is_calculated(): void
    {
        $order = $this->createSentOrder($this->branch, 'B05', [['Phở bò tái', StationCode::PHO]]);

        $this->actingAs($this->waiter)
            ->post(route('payments.start', $order), ['method' => 'cash'])
            ->assertRedirect(route('payments.cash', $order));

        $this->actingAs($this->waiter)
            ->post(route('payments.cash.pay', $order), ['tendered_amount' => 100_000])
            ->assertRedirect(route('payments.show', $order))
            ->assertSessionHas('success');

        $payment = Payment::query()->where('order_id', $order->id)->sole();
        $this->assertSame(PaymentMethod::CASH, $payment->method);
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertSame(50_000, (int) $payment->amount);
        $this->assertSame(100_000, (int) $payment->tendered_amount);
        $this->assertSame(50_000, (int) $payment->change_amount);
        $this->assertSame($this->waiter->id, $payment->confirmed_by);

        $this->actingAs($this->waiter)
            ->get(route('payments.show', $order))
            ->assertSee('Đã thanh toán đủ')
            ->assertDontSee('Tiếp tục thanh toán');
    }

    public function test_cash_payment_is_rejected_when_tendered_amount_is_not_enough_or_already_paid(): void
    {
        $order = $this->createSentOrder($this->branch, 'B05', [['Phở bò tái', StationCode::PHO]]);

        $this->actingAs($this->waiter)
            ->post(route('payments.cash.pay', $order), ['tendered_amount' => 20_000])
            ->assertSessionHasErrors('tendered_amount');
        $this->assertDatabaseCount('payments', 0);

        $this->actingAs($this->waiter)->post(route('payments.cash.pay', $order), ['tendered_amount' => 50_000]);
        $this->actingAs($this->waiter)
            ->post(route('payments.cash.pay', $order), ['tendered_amount' => 50_000])
            ->assertSessionHasErrors('payment');

        $this->assertSame(1, Payment::query()->where('order_id', $order->id)->count());
    }

    public function test_items_ordered_after_paying_are_charged_separately(): void
    {
        $order = $this->createSentOrder($this->branch, 'B05', [['Phở bò tái', StationCode::PHO], ['Trà đá', StationCode::DRINK]]);
        $this->actingAs($this->waiter)->post(route('payments.cash.pay', $order), ['tendered_amount' => 100_000]);

        // Khách gọi thêm 1 trà đá vào món đã trả (số lượng 1 → 2).
        $tea = $order->items()->where('item_name_snapshot', 'Trà đá')->firstOrFail();
        $tea->update(['quantity' => 2, 'line_total' => 100_000]);

        $this->actingAs($this->waiter)
            ->get(route('payments.show', $order))
            ->assertSee('Đã thanh toán')
            ->assertSee('Còn phải trả (món gọi thêm)')
            ->assertSee('50.000đ');

        $this->actingAs($this->waiter)->post(route('payments.cash.pay', $order), ['tendered_amount' => 50_000]);

        $this->assertSame(
            [100_000, 50_000],
            Payment::query()->where('order_id', $order->id)->orderBy('id')->pluck('amount')->map(fn ($amount) => (int) $amount)->all()
        );
    }

    public function test_order_screen_button_shows_paid_and_returns_when_more_items_are_sent(): void
    {
        $order = $this->createSentOrder($this->branch, 'B05', [['Phở bò tái', StationCode::PHO]]);
        $paidLabel = 'Đã thanh toán';

        $this->actingAs($this->waiter)
            ->get(route('orders.session', $order->session_id))
            ->assertOk()
            ->assertSee('Thanh toán')
            ->assertDontSee($paidLabel);

        $this->actingAs($this->waiter)->post(route('payments.cash.pay', $order), ['tendered_amount' => 50_000]);

        $this->actingAs($this->waiter)
            ->get(route('orders.session', $order->session_id))
            ->assertSee($paidLabel);

        // Khách gọi thêm và đã gửi bếp → nút "Thanh toán" hiện lại.
        $order->items()->first()->update(['quantity' => 2, 'line_total' => 100_000]);

        $this->actingAs($this->waiter)
            ->get(route('orders.session', $order->session_id))
            ->assertDontSee($paidLabel);
    }

    public function test_waiter_collects_by_mock_qr(): void
    {
        $order = $this->createSentOrder($this->branch, 'B05', [['Phở bò tái', StationCode::PHO]]);

        $response = $this->actingAs($this->waiter)->post(route('payments.start', $order), ['method' => 'qr']);
        $payment = Payment::query()->where('order_id', $order->id)->sole();
        $response->assertRedirect(route('payments.qr', $payment));

        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->actingAs($this->waiter)
            ->get(route('payments.qr', $payment))
            ->assertOk()
            ->assertSee('Đang chờ thanh toán')
            ->assertSee('MESA B05')
            ->assertSee('Hết hạn sau');

        $this->actingAs($this->waiter)
            ->post(route('payments.qr.confirm', $payment))
            ->assertRedirect(route('payments.show', $order));

        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertNotNull($payment->gateway_txn_id);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_vietqr_payload_contains_bank_account_amount_and_note(): void
    {
        config(['payment.bank' => ['bin' => '970422', 'name' => 'MB Bank', 'account_no' => '0123456789', 'account_name' => 'NGUYEN VAN A']]);

        $this->assertSame(
            '00020101021238540010A00000072701240006970422011001234567890208QRIBFTTA53037045405500005802VN62170813MESA B05 00126304'
                .substr(VietQrPayload::make(50_000, 'MESA B05 0012'), -4),
            VietQrPayload::make(50_000, 'MESA B05 0012')
        );
        $this->assertSame('Pho bo Ban 1', VietQrPayload::cleanNote('Phở bò, Bàn #1'));

        $order = $this->createSentOrder($this->branch, 'B05', [['Phở bò tái', StationCode::PHO]]);
        $this->actingAs($this->waiter)->post(route('payments.start', $order), ['method' => 'qr']);
        $payment = Payment::query()->where('order_id', $order->id)->sole();

        $this->actingAs($this->waiter)
            ->get(route('payments.qr', $payment))
            ->assertSee('data-payload="'.VietQrPayload::make(50_000, $payment->gateway_payload['transfer_note']).'"', false)
            ->assertSee('NGUYEN VAN A')
            ->assertSee('0123456789')
            ->assertSee('napas')
            ->assertDontSee('Mã mô phỏng');
    }

    public function test_qr_falls_back_to_mock_when_bank_account_is_not_configured(): void
    {
        config(['payment.bank.account_no' => '']);
        $order = $this->createSentOrder($this->branch, 'B05', [['Phở bò tái', StationCode::PHO]]);
        $this->actingAs($this->waiter)->post(route('payments.start', $order), ['method' => 'qr']);
        $payment = Payment::query()->where('order_id', $order->id)->sole();

        $this->actingAs($this->waiter)
            ->get(route('payments.qr', $payment))
            ->assertSee('Mã mô phỏng')
            ->assertDontSee('id="vietqr"', false);
    }

    public function test_expired_qr_cannot_be_confirmed_and_can_be_retried(): void
    {
        $order = $this->createSentOrder($this->branch, 'B05', [['Phở bò tái', StationCode::PHO]]);
        $this->actingAs($this->waiter)->post(route('payments.start', $order), ['method' => 'qr']);
        $expired = Payment::query()->where('order_id', $order->id)->sole();

        $this->travel(6)->minutes();

        $this->actingAs($this->waiter)
            ->post(route('payments.qr.confirm', $expired))
            ->assertSessionHasErrors('payment');

        $expired->refresh();
        $this->assertSame(PaymentStatus::FAILED, $expired->status);
        $this->assertSame('expired', $expired->gateway_payload['failure_reason']);

        $this->actingAs($this->waiter)
            ->get(route('payments.qr', $expired))
            ->assertSee('Hết hạn')
            ->assertSee('Tạo mã QR mới');

        $this->actingAs($this->waiter)->post(route('payments.start', $order), ['method' => 'qr']);
        $this->assertSame(1, Payment::query()->where('order_id', $order->id)->where('status', PaymentStatus::PENDING)->count());
    }

    public function test_failed_qr_then_switch_to_cash(): void
    {
        $order = $this->createSentOrder($this->branch, 'B05', [['Phở bò tái', StationCode::PHO]]);
        $this->actingAs($this->waiter)->post(route('payments.start', $order), ['method' => 'qr']);
        $qr = Payment::query()->where('order_id', $order->id)->sole();

        $this->actingAs($this->waiter)->post(route('payments.qr.fail', $qr))->assertRedirect(route('payments.qr', $qr));
        $this->assertSame(PaymentStatus::FAILED, $qr->fresh()->status);

        $this->actingAs($this->waiter)->post(route('payments.cash.pay', $order), ['tendered_amount' => 50_000]);

        $this->assertSame(1, Payment::query()->where('order_id', $order->id)->where('status', PaymentStatus::SUCCESS)->count());
    }

    public function test_kitchen_staff_cannot_open_payment_or_collect_money(): void
    {
        $order = $this->createSentOrder($this->branch, 'B05', [['Phở bò tái', StationCode::PHO]]);
        $kitchen = $this->createUserWithRole(ConstantHelper::ROLE_KITCHEN, $this->branch);

        $this->actingAs($kitchen)
            ->get(route('payments.show', $order))
            ->assertForbidden();

        $this->actingAs($kitchen)
            ->post(route('payments.cash.pay', $order), ['tendered_amount' => 50_000])
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_waiter_cannot_collect_for_another_branch(): void
    {
        $otherOrder = $this->createSentOrder($this->createBranch('PAY02'), 'C01', [['Phở gà', StationCode::PHO]]);

        $this->actingAs($this->waiter)
            ->post(route('payments.cash.pay', $otherOrder), ['tendered_amount' => 50_000])
            ->assertForbidden();
    }

    public function test_items_not_yet_sent_to_the_kitchen_are_not_charged(): void
    {
        $order = $this->createSentOrder($this->branch, 'B05', [['Phở bò tái', StationCode::PHO]]);
        $order->items()->first()->replicate()->fill(['status' => OrderItemStatus::PENDING, 'batch_id' => null])->save();

        $this->actingAs($this->waiter)
            ->get(route('payments.show', $order))
            ->assertSee('Tổng cần thanh toán')
            ->assertSeeInOrder(['Tổng cần thanh toán', '50.000đ']);

        $this->assertInstanceOf(Order::class, $order->fresh());
    }
}
