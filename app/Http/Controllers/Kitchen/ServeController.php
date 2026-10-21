<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kitchen;

use App\Enums\BatchStatus;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Kitchen\Concerns\ResolvesBranchScope;
use App\Models\KitchenTicket;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Màn hình phục vụ nhận món xong – MOD04, INV-18.
 * Bếp bấm Hoàn thành → món hiện ở "Món chờ phục vụ" → phục vụ mang ra bàn và bấm "Đã phục vụ".
 */
class ServeController extends Controller
{
    use ResolvesBranchScope;

    /**
     * Món vừa xong trong khoảng thời gian này (phút) sẽ hiện băng thông báo trên cùng.
     */
    public const JUST_READY_MINUTES = 3;

    public function index(Request $request): View
    {
        return view('kitchen.serve.index', $this->boardData($request->user()));
    }

    /**
     * Trả về phần danh sách món để màn hình phục vụ tự làm mới (polling).
     */
    public function board(Request $request): View
    {
        return view('kitchen.serve.partials.board', $this->boardData($request->user()));
    }

    /**
     * Phục vụ xác nhận đã mang các món đã xong của đơn ra bàn.
     */
    public function markServed(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        $this->abortUnlessBranchAccessible($this->serveBranchIds($request->user()), (int) $order->branch_id);

        $readyItems = $order->items()->where('status', OrderItemStatus::READY)->get();

        if ($readyItems->isEmpty()) {
            return $this->respond($request, 'Đơn này không còn món chờ phục vụ.', false);
        }

        DB::transaction(function () use ($request, $order, $readyItems): void {
            $now = now();

            foreach ($readyItems as $orderItem) {
                $orderItem->update(['status' => OrderItemStatus::SERVED]);

                OrderStatusHistory::query()->create([
                    'order_id' => $order->getKey(),
                    'order_item_id' => $orderItem->getKey(),
                    'batch_id' => $orderItem->batch_id,
                    'from_status' => OrderItemStatus::READY->value,
                    'to_status' => OrderItemStatus::SERVED->value,
                    'changed_by' => $request->user()->getKey(),
                    'note' => 'Phục vụ: '.$orderItem->item_name_snapshot,
                    'created_at' => $now,
                ]);
            }

            $readyItems->pluck('batch_id')->filter()->unique()->each(
                fn (int $batchId) => $this->syncServedBatch(OrderBatch::query()->findOrFail($batchId))
            );

            $this->syncServedOrder($order->fresh(), $request->user());
        });

        return $this->respond($request, 'Đã xác nhận phục vụ.');
    }

    /**
     * @return list<int>|null
     */
    private function serveBranchIds(User $user): ?array
    {
        return $this->accessibleBranchIds($user, ConstantHelper::PERM_ORDER_SERVE);
    }

    /**
     * @return array{readyGroups: Collection<int, array{order: Order, place: string, items: Collection<int, OrderItem>, readyAt: CarbonInterface}>, kitchenGroups: Collection<int, array{order: Order, place: string, isCooking: bool, firedAt: CarbonInterface, startedAt: CarbonInterface|null}>, justReady: array{order: Order, place: string, items: Collection<int, OrderItem>, readyAt: CarbonInterface}|null, overdueMinutes: int}
     */
    private function boardData(User $user): array
    {
        $branchIds = $this->serveBranchIds($user);
        $readyGroups = $this->readyGroups($branchIds);
        $latestReady = $readyGroups->sortByDesc(fn (array $group) => $group['readyAt'])->first();

        return [
            'readyGroups' => $readyGroups,
            'kitchenGroups' => $this->kitchenGroups($branchIds),
            'justReady' => $latestReady !== null && $latestReady['readyAt']->gte(now()->subMinutes(self::JUST_READY_MINUTES))
                ? $latestReady
                : null,
            'overdueMinutes' => KitchenDisplayController::OVERDUE_MINUTES,
        ];
    }

    /**
     * Món bếp đã xong nhưng chưa mang ra bàn, gom theo đơn (bàn). Đơn chờ lâu nhất xếp trên cùng.
     *
     * @param  list<int>|null  $branchIds
     * @return Collection<int, array{order: Order, place: string, items: Collection<int, OrderItem>, readyAt: CarbonInterface}>
     */
    private function readyGroups(?array $branchIds): Collection
    {
        return OrderItem::query()
            ->with([
                'modifiers',
                'kitchenTicketItems',
                'order.session.sessionTables' => fn ($query) => $query->whereNull('left_at')->with('diningTable'),
            ])
            ->where('status', OrderItemStatus::READY)
            ->whereHas('order', fn (Builder $query) => $query
                ->when($branchIds !== null, fn (Builder $orderQuery) => $orderQuery->whereIn('branch_id', $branchIds)))
            ->orderBy('id')
            ->get()
            ->groupBy('order_id')
            ->map(function (Collection $items): array {
                $order = $items->first()->order;

                return [
                    'order' => $order,
                    'place' => $this->placeLabel($order),
                    'items' => $items->values(),
                    'readyAt' => $items->flatMap->kitchenTicketItems->pluck('ready_at')->filter()->max() ?? $items->max('updated_at'),
                ];
            })
            ->sortBy(fn (array $group) => $group['readyAt'])
            ->values();
    }

    /**
     * Đơn còn món đang ở bếp (Mới / Đang nấu), gom theo đơn (bàn).
     *
     * @param  list<int>|null  $branchIds
     * @return Collection<int, array{order: Order, place: string, isCooking: bool, firedAt: CarbonInterface, startedAt: CarbonInterface|null}>
     */
    private function kitchenGroups(?array $branchIds): Collection
    {
        return KitchenTicket::query()
            ->with([
                'items',
                'order.session.sessionTables' => fn ($query) => $query->whereNull('left_at')->with('diningTable'),
            ])
            ->whereIn('status', [TicketStatus::NEW, TicketStatus::PREPARING])
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->orderBy('fired_at')
            ->get()
            ->groupBy('order_id')
            ->map(function (Collection $tickets): array {
                $order = $tickets->first()->order;

                return [
                    'order' => $order,
                    'place' => $this->placeLabel($order),
                    'isCooking' => $tickets->contains(fn (KitchenTicket $ticket): bool => $ticket->status === TicketStatus::PREPARING),
                    'firedAt' => $tickets->min('fired_at'),
                    'startedAt' => $tickets->flatMap->items->pluck('started_at')->filter()->min(),
                ];
            })
            ->values();
    }

    private function placeLabel(Order $order): string
    {
        $tableCodes = $order->session?->sessionTables
            ->map(fn ($sessionTable) => $sessionTable->diningTable?->code)
            ->filter()
            ->join(', ');

        return filled($tableCodes) ? 'Bàn '.$tableCodes : 'Mang về';
    }

    private function syncServedBatch(OrderBatch $batch): void
    {
        $hasUnservedItems = $batch->items()
            ->whereNotIn('status', [OrderItemStatus::SERVED, OrderItemStatus::CANCELLED])
            ->exists();

        if (! $hasUnservedItems && $batch->status !== BatchStatus::CANCELLED) {
            $batch->update(['status' => BatchStatus::SERVED, 'served_at' => now()]);
        }
    }

    private function syncServedOrder(Order $order, User $user): void
    {
        $hasUnservedItems = $order->items()
            ->whereNotIn('status', [OrderItemStatus::SERVED, OrderItemStatus::CANCELLED])
            ->exists();

        if ($hasUnservedItems || ! in_array($order->status, [OrderStatus::SENT, OrderStatus::IN_PROGRESS], true)) {
            return;
        }

        $previousStatus = $order->status;
        $order->update(['status' => OrderStatus::SERVED]);

        OrderStatusHistory::query()->create([
            'order_id' => $order->getKey(),
            'from_status' => $previousStatus->value,
            'to_status' => OrderStatus::SERVED->value,
            'changed_by' => $user->getKey(),
            'note' => 'Đã phục vụ đủ món',
            'created_at' => now(),
        ]);
    }

    private function respond(Request $request, string $message, bool $success = true): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $success ? 200 : 422);
        }

        return $success
            ? back()->with('success', $message)
            : back()->withErrors(['serve' => $message]);
    }
}
