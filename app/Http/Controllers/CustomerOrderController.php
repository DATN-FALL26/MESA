<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BatchStatus;
use App\Enums\BranchStatus;
use App\Enums\OrderChannel;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SessionStatus;
use App\Enums\ServeMode;
use App\Enums\TicketItemStatus;
use App\Enums\TicketStatus;
use App\Helpers\ConstantHelper;
use App\Models\Branch;
use App\Models\DiningSession;
use App\Models\DiningTable;
use App\Models\ItemVariant;
use App\Models\KitchenTicket;
use App\Models\KitchenTicketItem;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Station;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerOrderController extends Controller
{
    public function dashboard(): View
    {
        if (auth()->check()) {
            abort_unless(auth()->user()->hasPermission(ConstantHelper::PERM_DASHBOARD_VIEW), 403);

            return view('dashboard');
        }

        return $this->customerDashboard();
    }

    public function table(DiningTable $diningTable): View
    {
        abort_unless($diningTable->is_active && $diningTable->branch->status === BranchStatus::ACTIVE, 404);

        return $this->customerDashboard($diningTable);
    }

    public function qrUrl(DiningTable $diningTable): \Illuminate\Http\JsonResponse
    {
        abort_unless($diningTable->is_active && $diningTable->branch->status === BranchStatus::ACTIVE, 404);

        return response()->json([
            'table' => $diningTable->code,
            'url' => URL::signedRoute('customer.table', ['diningTable' => $diningTable->getKey()]),
        ]);
    }

    public function store(Request $request, DiningTable $diningTable): RedirectResponse
    {
        abort_unless($diningTable->is_active && $diningTable->branch->status === BranchStatus::ACTIVE, 404);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.menu_item_id' => ['required', 'integer', 'distinct', 'exists:menu_items,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:item_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:0', 'max:30'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $branchId = (int) $diningTable->branch_id;
        $selectedItems = array_values(array_filter(
            $validated['items'],
            static fn (array $item): bool => (int) $item['quantity'] > 0
        ));

        if ($selectedItems === []) {
            throw ValidationException::withMessages([
                'items' => 'Chọn số lượng ít nhất một món trước khi gửi đơn.',
            ]);
        }

        $order = DB::transaction(function () use ($request, $diningTable, $validated, $selectedItems, $branchId): Order {
            $session = $this->activeSessionForTable($diningTable, lock: true);

            if ($session === null) {
                throw ValidationException::withMessages([
                    'table' => 'Bàn này hiện chưa có lượt phục vụ đang mở. Vui lòng báo nhân viên.',
                ]);
            }

            $pricedItems = $this->priceOrderItems($selectedItems, $branchId);
            $branch = Branch::query()->findOrFail($branchId);
            $stationCodes = collect($pricedItems)
                ->map(static fn (array $item): string => $item['menuItem']->station_code->value)
                ->unique()
                ->values();
            $stations = Station::query()
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->whereIn('station_code', $stationCodes)
                ->get()
                ->keyBy(fn (Station $station): string => $station->station_code->value);

            foreach ($stationCodes as $stationCode) {
                if (! $stations->has($stationCode)) {
                    throw ValidationException::withMessages([
                        'items' => "Chi nhánh chưa cấu hình quầy chế biến cho nhóm món {$stationCode}.",
                    ]);
                }
            }

            $subtotal = array_sum(array_map(
                static fn (array $item): int => $item['unit_price'] * $item['quantity'],
                $pricedItems
            ));
            $taxAmount = array_sum(array_map(
                static fn (array $item): int => (int) round(
                    $item['unit_price'] * $item['quantity'] * (float) $item['menuItem']->tax_rate / 100
                ),
                $pricedItems
            ));
            $now = now();

            $order = Order::query()->create([
                'branch_id' => $branchId,
                'session_id' => $session->getKey(),
                'order_no' => 'WEB-'.Str::upper(Str::random(20)),
                'channel' => OrderChannel::DINE_IN,
                'status' => OrderStatus::SENT,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'note' => $validated['note'] ?? null,
                'created_by' => $request->user()?->getKey(),
            ]);

            $batch = OrderBatch::query()->create([
                'order_id' => $order->getKey(),
                'batch_no' => 1,
                'serve_mode' => ServeMode::TOGETHER,
                'status' => BatchStatus::SENT,
                'sent_at' => $now,
                'released_by' => null,
            ]);

            $orderItemsByStation = [];
            foreach ($pricedItems as $item) {
                $lineTotal = $item['unit_price'] * $item['quantity'];
                $orderItem = OrderItem::query()->create([
                    'order_id' => $order->getKey(),
                    'batch_id' => $batch->getKey(),
                    'menu_item_id' => $item['menuItem']->getKey(),
                    'variant_id' => $item['variant']?->getKey(),
                    'item_name_snapshot' => $item['menuItem']->name,
                    'variant_name_snapshot' => $item['variant']?->name,
                    'unit_price_snapshot' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'line_total' => $lineTotal,
                    'note' => $item['note'],
                    'status' => OrderItemStatus::SENT,
                ]);

                $orderItemsByStation[$item['menuItem']->station_code->value][] = $orderItem;
            }

            foreach ($orderItemsByStation as $stationCode => $orderItems) {
                $ticket = KitchenTicket::query()->create([
                    'branch_id' => $branchId,
                    'order_id' => $order->getKey(),
                    'batch_id' => $batch->getKey(),
                    'station_id' => $stations[$stationCode]->getKey(),
                    'ticket_no' => 'WEB-'.Str::upper(Str::random(22)),
                    'status' => TicketStatus::NEW,
                    'fired_at' => $now,
                ]);

                foreach ($orderItems as $orderItem) {
                    KitchenTicketItem::query()->create([
                        'ticket_id' => $ticket->getKey(),
                        'order_item_id' => $orderItem->getKey(),
                        'status' => TicketItemStatus::NEW,
                        'fire_at' => $now,
                    ]);
                }
            }

            Payment::query()->create([
                'branch_id' => $branch->getKey(),
                'order_id' => $order->getKey(),
                'method' => PaymentMethod::CASH,
                'amount' => $subtotal + $taxAmount,
                'status' => PaymentStatus::PENDING,
            ]);

            OrderStatusHistory::query()->create([
                'order_id' => $order->getKey(),
                'from_status' => null,
                'to_status' => OrderStatus::SENT->value,
                'changed_by' => $request->user()?->getKey(),
                'note' => 'Đặt món qua QR tại bàn '.$diningTable->code,
                'created_at' => $now,
            ]);

            return $order;
        });

        return redirect(URL::signedRoute('customer.table', ['diningTable' => $diningTable->getKey()]))
            ->with('order_success', [
                'order_no' => $order->order_no,
                'total_amount' => (int) $order->total_amount,
            ]);
    }

    private function customerDashboard(?DiningTable $diningTable = null): View
    {
        $branch = $diningTable?->branch;
        $session = $diningTable ? $this->activeSessionForTable($diningTable) : null;

        $items = $branch === null
            ? collect()
            : MenuItem::query()
                ->with([
                    'category',
                    'variants' => fn (Builder $query) => $query->where('is_active', true),
                    'branchMenuItems' => fn (Builder $query) => $query
                        ->where('branch_id', $branch->getKey())
                        ->where('is_available', true),
                ])
                ->where('is_active', true)
                ->whereHas('branchMenuItems', fn (Builder $query) => $query
                    ->where('branch_id', $branch->getKey())
                    ->where('is_available', true))
                ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
                ->orderBy('category_id')
                ->orderBy('name')
                ->get();

        return view('customer.menu', [
            'branch' => $branch,
            'diningTable' => $diningTable,
            'sessionIsOpen' => $session !== null,
            'items' => $items,
            'signedOrderUrl' => $diningTable === null
                ? null
                : URL::signedRoute('customer.orders.store', ['diningTable' => $diningTable->getKey()]),
            'branches' => $branch === null
                ? Branch::query()->where('status', BranchStatus::ACTIVE)->orderBy('name')->get()
                : collect(),
        ]);
    }

    private function activeSessionForTable(DiningTable $diningTable, bool $lock = false): ?DiningSession
    {
        $query = DiningSession::query()
            ->where('branch_id', $diningTable->branch_id)
            ->where('status', SessionStatus::OPEN)
            ->whereHas('sessionTables', fn (Builder $sessionTableQuery) => $sessionTableQuery
                ->where('table_id', $diningTable->getKey())
                ->whereNull('left_at'));

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * @param  array<int, array{menu_item_id: int|string, variant_id?: int|string|null, quantity: int|string, note?: string|null}>  $items
     * @return list<array{menuItem: MenuItem, variant: ItemVariant|null, unit_price: int, quantity: int, note: string|null}>
     */
    private function priceOrderItems(array $items, int $branchId): array
    {
        $pricedItems = [];

        foreach ($items as $item) {
            $menuItem = MenuItem::query()
                ->with(['variants' => fn (Builder $query) => $query->where('is_active', true)])
                ->whereKey($item['menu_item_id'])
                ->where('is_active', true)
                ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
                ->whereHas('branchMenuItems', fn (Builder $query) => $query
                    ->where('branch_id', $branchId)
                    ->where('is_available', true))
                ->first();

            if ($menuItem === null) {
                throw ValidationException::withMessages([
                    'items' => 'Một hoặc nhiều món hiện không có trong thực đơn của chi nhánh này.',
                ]);
            }

            $branchPrice = $menuItem->branchMenuItems()
                ->where('branch_id', $branchId)
                ->where('is_available', true)
                ->value('price');
            $unitPrice = (int) ($branchPrice ?? $menuItem->base_price);
            $variant = null;

            if (! empty($item['variant_id'])) {
                $variant = $menuItem->variants->firstWhere('id', (int) $item['variant_id']);

                if ($variant === null) {
                    throw ValidationException::withMessages([
                        'items' => 'Biến thể được chọn không thuộc món hoặc hiện không khả dụng.',
                    ]);
                }

                $unitPrice += (int) $variant->price_delta;
            }

            if ($unitPrice < 0) {
                throw ValidationException::withMessages([
                    'items' => 'Giá của món hoặc biến thể không hợp lệ.',
                ]);
            }

            $pricedItems[] = [
                'menuItem' => $menuItem,
                'variant' => $variant,
                'unit_price' => $unitPrice,
                'quantity' => (int) $item['quantity'],
                'note' => $item['note'] ?? null,
            ];
        }

        return $pricedItems;
    }
}
