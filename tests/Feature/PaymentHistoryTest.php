<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StationCode;
use App\Helpers\ConstantHelper;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsKitchenOrders;
use Tests\TestCase;

class PaymentHistoryTest extends TestCase
{
    use BuildsKitchenOrders;
    use RefreshDatabase;

    private Branch $branch;

    private User $waiter;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ReferenceSeeder::class);
        $this->withoutVite();
        config(['payment.bank.account_no' => '']);

        $this->branch = $this->createBranch('HIS01');
        $this->waiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER, $this->branch, 'Nhân viên Lan');
        $this->manager = $this->createUserWithRole(ConstantHelper::ROLE_BRANCH_MANAGER, $this->branch);
    }

    public function test_manager_sees_transaction_history_and_can_filter_by_method(): void
    {
        $cashOrder = $this->createSentOrder($this->branch, 'B05', [['Phở bò tái', StationCode::PHO]]);
        $this->actingAs($this->waiter)->post(route('payments.cash.pay', $cashOrder), ['tendered_amount' => 100_000]);

        $qrOrder = $this->createSentOrder($this->branch, 'B07', [['Phở gà', StationCode::PHO], ['Trà đá', StationCode::DRINK]]);
        $this->actingAs($this->waiter)->post(route('payments.start', $qrOrder), ['method' => 'qr']);
        $this->actingAs($this->waiter)->post(route('payments.qr.confirm', Payment::query()->where('order_id', $qrOrder->id)->sole()));

        $this->actingAs($this->manager)
            ->get(route('admin.reports'))
            ->assertOk()
            ->assertSee('Lịch sử giao dịch')
            ->assertSee($cashOrder->order_no)
            ->assertSee($qrOrder->order_no)
            ->assertSee('Nhân viên Lan')
            ->assertSeeInOrder(['Tổng đã thu', '150.000đ', 'Tiền mặt', '50.000đ', 'QR / chuyển khoản', '100.000đ']);

        $this->actingAs($this->manager)
            ->get(route('admin.reports', ['method' => 'cash']))
            ->assertSee($cashOrder->order_no)
            ->assertDontSee($qrOrder->order_no);
    }

    public function test_waiter_cannot_see_transaction_history(): void
    {
        $this->actingAs($this->waiter)
            ->get(route('admin.reports'))
            ->assertDontSee('Lịch sử giao dịch');
    }

    public function test_manager_only_sees_own_branch_transactions(): void
    {
        $otherBranch = $this->createBranch('HIS02');
        $otherOrder = $this->createSentOrder($otherBranch, 'C01', [['Phở bò tái', StationCode::PHO]]);
        $otherWaiter = $this->createUserWithRole(ConstantHelper::ROLE_WAITER, $otherBranch);
        $this->actingAs($otherWaiter)->post(route('payments.cash.pay', $otherOrder), ['tendered_amount' => 50_000]);

        $this->actingAs($this->manager)
            ->get(route('admin.reports'))
            ->assertDontSee($otherOrder->order_no)
            ->assertSee('Chưa có giao dịch');
    }

    public function test_dashboard_page_loads_payment_chart_from_api_without_merge_markers(): void
    {
        $this->actingAs($this->manager)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.dashboard.payment_stats', ['filter' => 'week']), false)
            ->assertDontSee('<<<<<<<', false)
            ->assertDontSee('>>>>>>>', false)
            ->assertDontSee('[120, 250]', false);
    }

    public function test_dashboard_payment_method_chart_uses_real_payments(): void
    {
        $order = $this->createSentOrder($this->branch, 'B05', [['Phở bò tái', StationCode::PHO]]);
        $this->actingAs($this->waiter)->post(route('payments.cash.pay', $order), ['tendered_amount' => 50_000]);

        $this->actingAs($this->manager)
            ->getJson(route('admin.dashboard.payment_stats'))
            ->assertOk()
            ->assertExactJson(['status' => 'success', 'data' => ['cash' => 50_000, 'transfer' => 0]]);
    }
}
