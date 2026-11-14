{{--
    MOD04 – INV-23: Lịch sử giao dịch (chỉ Quản lý). Nhúng vào trang Báo cáo chi tiết (MOD09),
    dùng chung khoảng "Từ ngày / Đến ngày" của trang, lọc thêm theo phương thức và trạng thái.
--}}
@php
    use App\Enums\PaymentMethod;
    use App\Services\Payment\OrderPaymentService;
    use App\Services\Payment\PaymentHistoryService;

    $historyFrom = (string) request('from_date', now()->subDays(7)->toDateString());
    $historyTo = (string) request('to_date', now()->toDateString());
    $historyMethod = (string) request('method', '');
    $historyStatus = (string) request('status', '');
    $history = app(PaymentHistoryService::class)->history(auth()->user(), $historyFrom, $historyTo, $historyMethod, $historyStatus);
    $historyMoney = fn (int $amount): string => number_format($amount, 0, ',', '.').'đ';
@endphp

<div id="lich-su-giao-dich" class="mt-6 rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h3 class="text-base font-bold text-gray-800">Lịch sử giao dịch</h3>
            <p class="text-sm text-gray-500">Từ {{ \Illuminate\Support\Carbon::parse($historyFrom)->format('d/m/Y') }} đến {{ \Illuminate\Support\Carbon::parse($historyTo)->format('d/m/Y') }}</p>
        </div>

        <form method="GET" action="{{ route('admin.reports') }}#lich-su-giao-dich" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="from_date" value="{{ $historyFrom }}">
            <input type="hidden" name="to_date" value="{{ $historyTo }}">
            <div>
                <label for="history-method" class="mb-1 block text-xs font-semibold uppercase text-gray-400">Phương thức</label>
                <select id="history-method" name="method" class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-700">
                    <option value="">Tất cả</option>
                    <option value="cash" @selected($historyMethod === 'cash')>Tiền mặt</option>
                    <option value="qr" @selected($historyMethod === 'qr')>QR / chuyển khoản</option>
                </select>
            </div>
            <div>
                <label for="history-status" class="mb-1 block text-xs font-semibold uppercase text-gray-400">Trạng thái</label>
                <select id="history-status" name="status" class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-700">
                    <option value="">Tất cả</option>
                    <option value="success" @selected($historyStatus === 'success')>Thành công</option>
                    <option value="pending" @selected($historyStatus === 'pending')>Đang chờ</option>
                    <option value="failed" @selected($historyStatus === 'failed')>Thất bại / Hết hạn</option>
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">Lọc giao dịch</button>
        </form>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-lg bg-gray-50 p-3">
            <p class="text-xs text-gray-500">Tổng đã thu</p>
            <p class="text-lg font-bold tabular-nums text-gray-900">{{ $historyMoney($history['totals']['cash'] + $history['totals']['transfer']) }}</p>
        </div>
        <div class="rounded-lg bg-emerald-50 p-3">
            <p class="text-xs text-emerald-700">Tiền mặt</p>
            <p class="text-lg font-bold tabular-nums text-emerald-800">{{ $historyMoney($history['totals']['cash']) }}</p>
        </div>
        <div class="rounded-lg bg-indigo-50 p-3">
            <p class="text-xs text-indigo-700">QR / chuyển khoản</p>
            <p class="text-lg font-bold tabular-nums text-indigo-800">{{ $historyMoney($history['totals']['transfer']) }}</p>
        </div>
        <div class="rounded-lg bg-gray-50 p-3">
            <p class="text-xs text-gray-500">Giao dịch thành công</p>
            <p class="text-lg font-bold tabular-nums text-gray-900">{{ $history['successCount'] }}</p>
        </div>
    </div>

    <div class="mt-4 overflow-x-auto">
        <table class="w-full border-collapse text-left">
            <thead>
                <tr class="border-b text-xs font-semibold uppercase text-gray-400">
                    <th class="px-3 py-3">Mã order</th>
                    <th class="px-3 py-3">Bàn</th>
                    <th class="px-3 py-3">Thời gian</th>
                    <th class="px-3 py-3">Phương thức</th>
                    <th class="px-3 py-3 text-right">Số tiền</th>
                    <th class="px-3 py-3">Trạng thái</th>
                    <th class="px-3 py-3">Nhân viên thu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse ($history['payments'] as $historyPayment)
                    @php
                        $historyLabel = OrderPaymentService::statusLabel($historyPayment);
                    @endphp
                    <tr>
                        <td class="px-3 py-3 font-medium text-gray-800">{{ $historyPayment->order?->order_no ?? '#'.$historyPayment->order_id }}</td>
                        <td class="px-3 py-3 text-gray-600">{{ PaymentHistoryService::tableLabel($historyPayment) }}</td>
                        <td class="px-3 py-3 tabular-nums text-gray-600">{{ ($historyPayment->paid_at ?? $historyPayment->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-3 text-gray-600">{{ $historyPayment->method === PaymentMethod::CASH ? 'Tiền mặt' : 'QR / chuyển khoản' }}</td>
                        <td class="px-3 py-3 text-right font-semibold tabular-nums text-gray-900">{{ $historyMoney((int) $historyPayment->amount) }}</td>
                        <td class="px-3 py-3">
                            <span @class([
                                'rounded-full px-2 py-1 text-xs font-semibold',
                                'bg-emerald-50 text-emerald-700' => $historyLabel === 'Thành công',
                                'bg-amber-50 text-amber-700' => $historyLabel === 'Đang chờ',
                                'bg-rose-50 text-rose-600' => ! in_array($historyLabel, ['Thành công', 'Đang chờ'], true),
                            ])>{{ $historyLabel }}</span>
                        </td>
                        <td class="px-3 py-3 text-gray-600">{{ $historyPayment->confirmer?->full_name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-xs text-gray-400">Chưa có giao dịch trong khoảng thời gian này.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($history['payments']->hasPages())
        <div class="mt-4">{{ $history['payments']->fragment('lich-su-giao-dich')->links() }}</div>
    @endif
</div>
