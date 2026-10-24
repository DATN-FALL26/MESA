<?php

declare(strict_types=1);

namespace App\Services\Kitchen;

use App\Enums\BatchStatus;
use App\Enums\ItemType;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\TicketItemStatus;
use App\Enums\TicketStatus;
use App\Models\KitchenTicket;
use App\Models\KitchenTicketItem;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * MOD04 – M04-T03: nhận món từ Order (MOD03) để tạo phiếu bếp cho màn hình KDS.
 *
 * MOD03 gọi khi phục vụ bấm "Gửi bếp":
 *     app(KitchenTicketDispatcher::class)->sendBatch($batch, auth()->user());
 *
 * - Món được chia theo quầy chế biến (Phở / Nước / Món kèm), mỗi quầy một phiếu.
 * - Gửi nhiều lần trên cùng order: chỉ món chưa từng gửi bếp mới được tạo phiếu, món cũ không bị tạo lại.
 * - Món đã hủy bị bỏ qua.
 * - Phiếu chỉ gồm đồ có sẵn (nước, quẩy) vào thẳng "Đang nấu" để nhân viên chuẩn bị.
 * - Phiếu trả món chỉ được in khi bếp bấm "Hoàn thành" (xem KitchenTicketPrinter).
 */
class KitchenTicketDispatcher
{
    /**
     * Gửi các món chưa gửi bếp của một đợt gọi món xuống bếp.
     *
     * @return Collection<int, KitchenTicket> Các phiếu bếp vừa tạo (rỗng nếu không còn món mới).
     *
     * @throws ValidationException Khi chi nhánh chưa cấu hình quầy chế biến cho nhóm món.
     */
    public function sendBatch(OrderBatch $batch, ?User $sentBy = null): Collection
    {
        return DB::transaction(function () use ($batch, $sentBy): Collection {
            $batch = OrderBatch::query()->lockForUpdate()->findOrFail($batch->getKey());
            $order = Order::query()->lockForUpdate()->findOrFail($batch->order_id);

            $unsentItems = $batch->items()
                ->with('menuItem')
                ->where('status', '!=', OrderItemStatus::CANCELLED)
                ->whereDoesntHave('kitchenTicketItems')
                ->orderBy('id')
                ->get();

            if ($unsentItems->isEmpty()) {
                return collect();
            }

            $itemsByStation = $unsentItems->groupBy(
                fn (OrderItem $orderItem): string => $orderItem->menuItem->station_code->value
            );
            $stations = $this->stationsFor((int) $order->branch_id, $itemsByStation->keys());
            $now = now();

            $tickets = $itemsByStation->map(function (Collection $orderItems, string $stationCode) use ($order, $batch, $stations, $now, $sentBy): KitchenTicket {
                // Đồ có sẵn (nước, quẩy...) không cần nấu: vào thẳng "Đang nấu" để nhân viên chuẩn bị, bấm Hoàn thành 1 lần.
                $isReadyMade = $orderItems->every(fn (OrderItem $orderItem): bool => self::isReadyMade($orderItem));

                $ticket = KitchenTicket::query()->create([
                    'branch_id' => $order->branch_id,
                    'order_id' => $order->getKey(),
                    'batch_id' => $batch->getKey(),
                    'station_id' => $stations[$stationCode]->getKey(),
                    'ticket_no' => $this->newTicketNumber(),
                    'status' => $isReadyMade ? TicketStatus::PREPARING : TicketStatus::NEW,
                    'fired_at' => $now,
                    'updated_by' => $sentBy?->getKey(),
                ]);

                foreach ($orderItems as $orderItem) {
                    KitchenTicketItem::query()->create([
                        'ticket_id' => $ticket->getKey(),
                        'order_item_id' => $orderItem->getKey(),
                        'status' => $isReadyMade ? TicketItemStatus::PREPARING : TicketItemStatus::NEW,
                        'fire_at' => $now,
                        'started_at' => $isReadyMade ? $now : null,
                    ]);

                    $orderItem->update(['status' => $isReadyMade ? OrderItemStatus::PREPARING : OrderItemStatus::SENT]);
                }

                return $ticket;
            })->values();

            $batch->update([
                'status' => BatchStatus::SENT,
                'sent_at' => $batch->sent_at ?? $now,
                'released_by' => $batch->released_by ?? $sentBy?->getKey(),
            ]);

            if (in_array($order->status, [OrderStatus::DRAFT, OrderStatus::SERVED], true)) {
                $previousStatus = $order->status;
                $order->update(['status' => OrderStatus::SENT]);

                OrderStatusHistory::query()->create([
                    'order_id' => $order->getKey(),
                    'batch_id' => $batch->getKey(),
                    'from_status' => $previousStatus->value,
                    'to_status' => OrderStatus::SENT->value,
                    'changed_by' => $sentBy?->getKey(),
                    'note' => 'Gửi bếp đợt '.$batch->batch_no.' ('.$unsentItems->count().' món)',
                    'created_at' => $now,
                ]);
            }

            return $tickets;
        });
    }

    /**
     * Đồ có sẵn, không cần nấu: đồ uống và món kèm (vd. trà đá, nước ngọt, quẩy).
     */
    public static function isReadyMade(OrderItem $orderItem): bool
    {
        return in_array($orderItem->menuItem?->item_type, [ItemType::DRINK, ItemType::SIDE], true);
    }

    /**
     * Gửi tất cả món chưa gửi bếp của một order (mọi đợt gọi món).
     *
     * @return Collection<int, KitchenTicket>
     */
    public function sendOrder(Order $order, ?User $sentBy = null): Collection
    {
        return $order->batches()
            ->where('status', '!=', BatchStatus::CANCELLED)
            ->orderBy('batch_no')
            ->get()
            ->flatMap(fn (OrderBatch $batch): Collection => $this->sendBatch($batch, $sentBy))
            ->values();
    }

    /**
     * @param  Collection<int, string>  $stationCodes
     * @return Collection<string, Station>
     */
    private function stationsFor(int $branchId, Collection $stationCodes): Collection
    {
        $stations = Station::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereIn('station_code', $stationCodes->all())
            ->get()
            ->keyBy(fn (Station $station): string => $station->station_code->value);

        $missingStationCodes = $stationCodes->reject(fn (string $stationCode): bool => $stations->has($stationCode));

        if ($missingStationCodes->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Chi nhánh chưa cấu hình quầy chế biến cho nhóm món: '.$missingStationCodes->join(', ').'.',
            ]);
        }

        return $stations;
    }

    private function newTicketNumber(): string
    {
        return 'KT-'.now()->format('ymdHis').'-'.Str::upper(Str::random(5));
    }
}
