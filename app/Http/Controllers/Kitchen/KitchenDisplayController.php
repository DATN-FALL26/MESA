<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kitchen;

use App\Enums\BatchStatus;
use App\Enums\NotificationType;
use App\Enums\OrderItemStatus;
use App\Enums\StationCode;
use App\Enums\TicketItemStatus;
use App\Enums\TicketStatus;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Kitchen\Concerns\ResolvesBranchScope;
use App\Models\KitchenTicket;
use App\Models\KitchenTicketItem;
use App\Models\Notification;
use App\Models\OrderBatch;
use App\Models\OrderStatusHistory;
use App\Models\PrintJob;
use App\Models\User;
use App\Services\Kitchen\KitchenTicketDispatcher;
use App\Services\Kitchen\KitchenTicketPrinter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Màn hình bếp (KDS) – MOD04: INV-15, INV-16, INV-17 (in phiếu trả món) & INV-18 (báo món xong).
 */
class KitchenDisplayController extends Controller
{
    use ResolvesBranchScope;

    /**
     * Phiếu đã được phục vụ mang ra bàn còn hiện ở cột "Hoàn thành" thêm khoảng thời gian này (giây) rồi ẩn.
     */
    public const SERVED_VISIBLE_SECONDS = 60;

    /**
     * Phiếu chờ lâu hơn số phút này sẽ được đánh dấu "Quá giờ".
     */
    public const OVERDUE_MINUTES = 10;

    public function __construct(private KitchenTicketPrinter $printer) {}

    public function index(Request $request): View
    {
        $stationCode = $this->selectedStationCode($request);
        $branchIds = $this->kitchenBranchIds($request->user());
        $columns = $this->boardColumns($branchIds, $stationCode);

        return view('kitchen.index', [
            'columns' => $columns,
            'printJobs' => $this->printJobsFor($columns),
            'phoBowlStats' => $this->phoBowlStats($branchIds),
            'stationCode' => $stationCode,
            'stationCodes' => [StationCode::PHO, StationCode::SIDE],
            'overdueMinutes' => self::OVERDUE_MINUTES,
        ]);
    }

    /**
     * Trả về phần bảng phiếu bếp để màn hình KDS tự làm mới (polling).
     */
    public function board(Request $request): View
    {
        $branchIds = $this->kitchenBranchIds($request->user());
        $stationCode = $this->selectedStationCode($request);
        $columns = $this->boardColumns($branchIds, $stationCode);

        return view('kitchen.partials.board', [
            'columns' => $columns,
            'printJobs' => $this->printJobsFor($columns),
            'phoBowlStats' => $this->phoBowlStats($branchIds),
            'overdueMinutes' => self::OVERDUE_MINUTES,
        ]);
    }

    /**
     * Chuyển một món sang trạng thái kế tiếp: Mới → Đang nấu → Hoàn thành.
     */
    public function advanceItem(Request $request, KitchenTicketItem $kitchenTicketItem): RedirectResponse|JsonResponse
    {
        $ticket = $kitchenTicketItem->ticket;
        $this->abortUnlessBranchAccessible($this->kitchenBranchIds($request->user()), (int) $ticket->branch_id);

        $nextStatus = $this->nextItemStatus($kitchenTicketItem->status);

        if ($nextStatus === null) {
            return $this->respond($request, 'Món này đã hoàn thành hoặc đã bị hủy.', false);
        }

        DB::transaction(function () use ($request, $ticket, $kitchenTicketItem, $nextStatus): void {
            $this->moveItemTo($kitchenTicketItem, $nextStatus, $request->user());
            $this->syncTicketAndBatch($ticket, $request->user());
        });

        return $this->respond($request, 'Đã cập nhật trạng thái món.');
    }

    /**
     * Chuyển cả phiếu: còn món Mới thì bắt đầu nấu tất cả, ngược lại hoàn thành tất cả.
     */
    public function advanceTicket(Request $request, KitchenTicket $kitchenTicket): RedirectResponse|JsonResponse
    {
        $this->abortUnlessBranchAccessible($this->kitchenBranchIds($request->user()), (int) $kitchenTicket->branch_id);

        $items = $kitchenTicket->items()
            ->with('orderItem.menuItem')
            ->whereIn('status', [TicketItemStatus::NEW, TicketItemStatus::SCHEDULED, TicketItemStatus::PREPARING])
            ->get();

        if ($items->isEmpty()) {
            return $this->respond($request, 'Phiếu này không còn món cần chế biến.', false);
        }

        $hasWaitingItems = $items->contains(
            fn (KitchenTicketItem $item): bool => $item->status !== TicketItemStatus::PREPARING
        );
        // Nước / quẩy (cột "Nước & Quẩy") chỉ cần bấm "Xong" một lần.
        $targetStatus = $hasWaitingItems && ! $this->isReadyMadeTicketItems($items)
            ? TicketItemStatus::PREPARING
            : TicketItemStatus::READY;

        DB::transaction(function () use ($request, $kitchenTicket, $items, $targetStatus): void {
            foreach ($items as $item) {
                if ($item->status !== $targetStatus) {
                    $this->moveItemTo($item, $targetStatus, $request->user());
                }
            }

            $this->syncTicketAndBatch($kitchenTicket, $request->user());
        });

        return $this->respond($request, 'Đã cập nhật trạng thái phiếu bếp.');
    }

    /**
     * In lại phiếu trả món: gửi thêm một lệnh in tới máy in của quầy và mở phiếu để in qua trình duyệt.
     * (Lần in đầu diễn ra tự động, ngầm, khi bếp bấm "Hoàn thành".)
     */
    public function printTicket(Request $request, KitchenTicket $kitchenTicket): View
    {
        $branchIds = $this->accessibleBranchIds($request->user(), ConstantHelper::PERM_KITCHEN_PRINT);
        $this->abortUnlessBranchAccessible($branchIds, (int) $kitchenTicket->branch_id);
        abort_unless($kitchenTicket->status === TicketStatus::READY, 422, 'Phiếu chưa hoàn thành nên chưa in được.');

        $printJob = $this->printer->reprint($kitchenTicket);

        $kitchenTicket->load([
            'station.printer',
            'order.creator',
            'order.session.openedByUser',
            'order.session.sessionTables' => fn ($query) => $query->whereNull('left_at')->with('diningTable'),
            'items' => fn ($query) => $query
                ->where('status', '!=', TicketItemStatus::CANCELLED)
                ->orderBy('id')
                ->with(['orderItem.modifiers', 'orderItem.menuItem']),
        ]);

        return view('kitchen.print-ticket', [
            'ticket' => $kitchenTicket,
            'printJob' => $printJob,
        ]);
    }

    /**
     * @return list<int>|null
     */
    private function kitchenBranchIds(User $user): ?array
    {
        return $this->accessibleBranchIds($user, ConstantHelper::PERM_KITCHEN_VIEW);
    }

    /**
     * Chia phiếu bếp thành 4 cột: Mới / Đang nấu / Nước & Quẩy / Hoàn thành.
     * - Mới, Đang nấu: chỉ món phải nấu (phở...).
     * - Nước & Quẩy: phiếu chỉ gồm đồ có sẵn, chưa hoàn thành.
     * - Hoàn thành: giữ phiếu xong trong hôm nay cho tới khi phục vụ mang ra, sau đó hiện thêm 1 phút rồi ẩn.
     *
     * @param  list<int>|null  $branchIds
     * @return array{new: Collection<int, KitchenTicket>, preparing: Collection<int, KitchenTicket>, quick: Collection<int, KitchenTicket>, done: Collection<int, KitchenTicket>}
     */
    private function boardColumns(?array $branchIds, ?StationCode $stationCode): array
    {
        $tickets = KitchenTicket::query()
            ->with([
                'station.printer',
                'order.session.sessionTables' => fn ($query) => $query->whereNull('left_at')->with('diningTable'),
                'items' => fn ($query) => $query
                    ->where('status', '!=', TicketItemStatus::CANCELLED)
                    ->orderBy('id')
                    ->with(['orderItem.modifiers', 'orderItem.menuItem']),
            ])
            ->where(fn (Builder $query) => $query
                ->whereIn('status', [TicketStatus::NEW, TicketStatus::PREPARING])
                ->orWhere(fn (Builder $doneQuery) => $doneQuery
                    ->where('status', TicketStatus::READY)
                    ->where('ready_at', '>=', today())))
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->when($stationCode !== null, fn (Builder $query) => $query->whereHas(
                'station',
                fn (Builder $stationQuery) => $stationQuery->where('station_code', $stationCode)
            ))
            ->orderBy('fired_at')
            ->orderBy('id')
            ->get();

        [$readyMadeTickets, $cookedTickets] = $tickets
            ->whereIn('status', [TicketStatus::NEW, TicketStatus::PREPARING])
            ->partition(fn (KitchenTicket $ticket): bool => $this->isReadyMadeTicketItems($ticket->items));

        return [
            'new' => $cookedTickets->where('status', TicketStatus::NEW)->values(),
            'preparing' => $cookedTickets->where('status', TicketStatus::PREPARING)->values(),
            'quick' => $readyMadeTickets->values(),
            'done' => $tickets
                ->where('status', TicketStatus::READY)
                ->filter(fn (KitchenTicket $ticket): bool => $this->isStillShownAsDone($ticket))
                ->sortByDesc('ready_at')
                ->values(),
        ];
    }

    /**
     * Phiếu chỉ gồm đồ có sẵn (nước, quẩy) – không cần nấu.
     *
     * @param  Collection<int, KitchenTicketItem>  $ticketItems
     */
    private function isReadyMadeTicketItems(Collection $ticketItems): bool
    {
        return $ticketItems->isNotEmpty() && $ticketItems->every(
            fn (KitchenTicketItem $ticketItem): bool => KitchenTicketDispatcher::isReadyMade($ticketItem->orderItem)
        );
    }

    /**
     * Phiếu chưa được phục vụ hết thì luôn hiện; đã phục vụ hết thì chỉ hiện thêm SERVED_VISIBLE_SECONDS.
     */
    private function isStillShownAsDone(KitchenTicket $ticket): bool
    {
        $orderItems = $ticket->items->map(fn (KitchenTicketItem $ticketItem) => $ticketItem->orderItem);
        $isFullyServed = $orderItems->isNotEmpty()
            && $orderItems->every(fn ($orderItem): bool => $orderItem->status === OrderItemStatus::SERVED);

        if (! $isFullyServed) {
            return true;
        }

        return $orderItems->max('updated_at')->gte(now()->subSeconds(self::SERVED_VISIBLE_SECONDS));
    }

    /**
     * @param  array{new: Collection<int, KitchenTicket>, preparing: Collection<int, KitchenTicket>, quick: Collection<int, KitchenTicket>, done: Collection<int, KitchenTicket>}  $columns
     * @return SupportCollection<int, PrintJob>
     */
    private function printJobsFor(array $columns): SupportCollection
    {
        $ticketIds = collect($columns)->flatten(1)->pluck('id');

        return $this->printer->latestJobsFor($ticketIds);
    }

    /**
     * Đếm số bát phở (món của quầy Phở, tính theo số lượng): chưa nấu (Mới + Đang nấu) và đã nấu xong trong hôm nay.
     *
     * @param  list<int>|null  $branchIds
     * @return array{pending: int, done: int}
     */
    private function phoBowlStats(?array $branchIds): array
    {
        $phoItems = fn () => KitchenTicketItem::query()
            ->join(ConstantHelper::TABLE_ORDER_ITEMS, ConstantHelper::TABLE_ORDER_ITEMS.'.id', '=', ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS.'.order_item_id')
            ->whereHas('ticket', fn (Builder $ticketQuery) => $ticketQuery
                ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
                ->whereHas('station', fn (Builder $stationQuery) => $stationQuery->where('station_code', StationCode::PHO)));

        return [
            'pending' => (int) $phoItems()
                ->whereIn(ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS.'.status', [
                    TicketItemStatus::NEW, TicketItemStatus::SCHEDULED, TicketItemStatus::PREPARING,
                ])
                ->sum(ConstantHelper::TABLE_ORDER_ITEMS.'.quantity'),
            'done' => (int) $phoItems()
                ->where(ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS.'.status', TicketItemStatus::READY)
                ->where(ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS.'.ready_at', '>=', today())
                ->sum(ConstantHelper::TABLE_ORDER_ITEMS.'.quantity'),
        ];
    }

    private function selectedStationCode(Request $request): ?StationCode
    {
        $validated = $request->validate([
            'station' => ['nullable', Rule::enum(StationCode::class)],
        ]);

        return isset($validated['station']) ? StationCode::from($validated['station']) : null;
    }

    private function nextItemStatus(TicketItemStatus $status): ?TicketItemStatus
    {
        return match ($status) {
            TicketItemStatus::NEW, TicketItemStatus::SCHEDULED => TicketItemStatus::PREPARING,
            TicketItemStatus::PREPARING => TicketItemStatus::READY,
            default => null,
        };
    }

    /**
     * Đổi trạng thái món trên phiếu bếp, đồng bộ sang dòng món của đơn và ghi log người thực hiện.
     */
    private function moveItemTo(KitchenTicketItem $item, TicketItemStatus $status, User $user): void
    {
        $previousStatus = $item->status;
        $now = now();

        $item->fill(['status' => $status]);

        if ($status === TicketItemStatus::PREPARING) {
            $item->started_at = $now;
        }

        if ($status === TicketItemStatus::READY) {
            $item->started_at ??= $now;
            $item->ready_at = $now;
        }

        $item->save();

        $orderItem = $item->orderItem;
        $orderItem->update([
            'status' => $status === TicketItemStatus::READY ? OrderItemStatus::READY : OrderItemStatus::PREPARING,
        ]);

        OrderStatusHistory::query()->create([
            'order_id' => $orderItem->order_id,
            'order_item_id' => $orderItem->getKey(),
            'batch_id' => $orderItem->batch_id,
            'from_status' => $previousStatus->value,
            'to_status' => $status->value,
            'changed_by' => $user->getKey(),
            'note' => 'KDS: '.$orderItem->item_name_snapshot,
            'created_at' => $now,
        ]);
    }

    /**
     * Tính lại trạng thái phiếu bếp và đợt gọi món dựa trên trạng thái các món.
     */
    private function syncTicketAndBatch(KitchenTicket $ticket, User $user): void
    {
        $itemStatuses = $ticket->items()
            ->where('status', '!=', TicketItemStatus::CANCELLED)
            ->pluck('status');

        $allReady = $itemStatuses->isNotEmpty()
            && $itemStatuses->every(fn (TicketItemStatus $status): bool => $status === TicketItemStatus::READY);
        $anyStarted = $itemStatuses->contains(
            fn (TicketItemStatus $status): bool => in_array($status, [TicketItemStatus::PREPARING, TicketItemStatus::READY], true)
        );

        $wasReady = $ticket->status === TicketStatus::READY;

        $ticket->update([
            'status' => match (true) {
                $allReady => TicketStatus::READY,
                $anyStarted => TicketStatus::PREPARING,
                default => TicketStatus::NEW,
            },
            'ready_at' => $allReady ? now() : null,
            'updated_by' => $user->getKey(),
        ]);

        // INV-17: bếp bấm "Hoàn thành" thì tự in phiếu trả món.
        if ($allReady && ! $wasReady) {
            $this->printer->autoPrint($ticket);
        }

        if ($ticket->batch_id !== null) {
            $this->syncBatch($ticket->batch);
        }
    }

    private function syncBatch(OrderBatch $batch): void
    {
        if (in_array($batch->status, [BatchStatus::SERVED, BatchStatus::CANCELLED], true)) {
            return;
        }

        $previousStatus = $batch->status;
        $itemStatuses = $batch->items()
            ->where('status', '!=', OrderItemStatus::CANCELLED)
            ->pluck('status');

        $readyStatuses = [OrderItemStatus::READY, OrderItemStatus::SERVED];
        $readyCount = $itemStatuses->filter(fn (OrderItemStatus $status): bool => in_array($status, $readyStatuses, true))->count();
        $anyPreparing = $itemStatuses->contains(OrderItemStatus::PREPARING);

        $status = match (true) {
            $itemStatuses->isNotEmpty() && $readyCount === $itemStatuses->count() => BatchStatus::ALL_READY,
            $readyCount > 0 => BatchStatus::PARTIALLY_READY,
            $anyPreparing => BatchStatus::COOKING,
            default => $batch->status,
        };

        $batch->update([
            'status' => $status,
            'all_ready_at' => $status === BatchStatus::ALL_READY ? now() : null,
        ]);

        if ($status === BatchStatus::ALL_READY && $previousStatus !== BatchStatus::ALL_READY) {
            $this->notifyWaitersBatchReady($batch);
        }
    }

    /**
     * INV-18: phát thông báo cho phục vụ khi toàn bộ món của đợt đã xong.
     */
    private function notifyWaitersBatchReady(OrderBatch $batch): void
    {
        $batch->loadMissing([
            'order.session.sessionTables' => fn ($query) => $query->whereNull('left_at')->with('diningTable'),
            'items' => fn ($query) => $query->where('status', '!=', OrderItemStatus::CANCELLED),
        ]);

        $order = $batch->order;
        $tableCodes = $order->session?->sessionTables
            ->map(fn ($sessionTable) => $sessionTable->diningTable?->code)
            ->filter()
            ->join(', ');
        $place = filled($tableCodes) ? 'Bàn '.$tableCodes : 'Đơn mang về '.$order->order_no;

        Notification::query()->create([
            'branch_id' => $order->branch_id,
            'target_role_code' => ConstantHelper::ROLE_WAITER,
            'type' => NotificationType::BATCH_ALL_READY,
            'title' => $place.' đã xong món, mang ra bàn',
            'body' => $batch->items
                ->map(fn ($orderItem) => $orderItem->quantity.'x '.$orderItem->item_name_snapshot)
                ->join(', '),
            'payload' => [
                'order_id' => $order->getKey(),
                'batch_id' => $batch->getKey(),
            ],
            'expires_at' => now()->addHours(2),
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
            : back()->withErrors(['kitchen' => $message]);
    }
}
