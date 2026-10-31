<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BatchStatus;
use App\Enums\BranchStatus;
use App\Enums\OrderChannel;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\ServeMode;
use App\Enums\SessionStatus;
use App\Enums\StationCode;
use App\Enums\TableStatus;
use App\Helpers\ConstantHelper;
use App\Models\Branch;
use App\Models\DiningSession;
use App\Models\DiningTable;
use App\Models\ItemVariant;
use App\Models\MenuItem;
use App\Models\Modifier;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\SessionTable;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Kitchen\KitchenTicketDispatcher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dữ liệu mẫu cho màn hình bếp (KDS) – MOD04.
 * Mở lượt dùng bữa cho vài bàn trống và tạo order kèm phiếu bếp ở các quầy.
 * Chạy: php artisan db:seed --class=KitchenDemoSeeder
 */
class KitchenDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Kitchen demo data can only be seeded in local or testing environments.');
        }

        $branch = Branch::query()->where('status', BranchStatus::ACTIVE)->orderBy('id')->firstOrFail();
        $tables = DiningTable::query()
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->where('status', TableStatus::AVAILABLE)
            ->orderBy('id')
            ->limit(3)
            ->get();

        if ($tables->count() < 3) {
            $this->command?->warn('Cần ít nhất 3 bàn trống để tạo dữ liệu mẫu KDS.');

            return;
        }

        $menuItems = MenuItem::query()->where('is_active', true)->orderBy('id')->get();
        $phoItems = $menuItems->where('station_code', StationCode::PHO)->values();
        $drinkItems = $menuItems->where('station_code', StationCode::DRINK)->values();
        $sideItems = $menuItems->where('station_code', StationCode::SIDE)->values();
        $extraEgg = Modifier::query()->where('name', 'like', '%trứng%')->first();
        $openerId = UserRole::query()
            ->whereHas('role', fn ($query) => $query->where('code', ConstantHelper::ROLE_WAITER))
            ->value('user_id') ?? User::query()->orderBy('id')->value('id');

        $demoOrders = [
            [
                'table' => $tables[0],
                'minutes_ago' => 25,
                'items' => [
                    ['menu_item' => $phoItems->get(0), 'variant' => 'Lớn', 'quantity' => 2, 'note' => '1 tô ít bánh', 'modifier' => $extraEgg],
                    ['menu_item' => $drinkItems->get(0), 'quantity' => 2],
                ],
            ],
            [
                'table' => $tables[1],
                'minutes_ago' => 12,
                'items' => [
                    ['menu_item' => $phoItems->get(2), 'quantity' => 1, 'note' => 'Không hành'],
                    ['menu_item' => $sideItems->get(0), 'quantity' => 2],
                ],
            ],
            [
                'table' => $tables[2],
                'minutes_ago' => 2,
                'items' => [
                    ['menu_item' => $phoItems->get(1), 'variant' => 'Nhỏ', 'quantity' => 1],
                    ['menu_item' => $drinkItems->get(1), 'quantity' => 1],
                ],
            ],
        ];

        DB::transaction(function () use ($branch, $demoOrders, $openerId): void {
            foreach ($demoOrders as $demoOrder) {
                $this->createDemoOrder($branch, $demoOrder, (int) $openerId);
            }
        });

        $this->command?->info('Đã tạo 3 order mẫu cho màn hình bếp tại '.$branch->name.'.');
    }

    /**
     * @param  array{table: DiningTable, minutes_ago: int, items: list<array{menu_item: MenuItem|null, variant?: string, quantity: int, note?: string, modifier?: Modifier|null}>}  $demoOrder
     */
    private function createDemoOrder(Branch $branch, array $demoOrder, int $openerId): void
    {
        $firedAt = now()->subMinutes($demoOrder['minutes_ago']);
        $table = $demoOrder['table'];

        $session = DiningSession::query()->create([
            'branch_id' => $branch->id,
            'opened_by' => $openerId,
            'opened_at' => $firedAt->copy()->subMinutes(5),
            'status' => SessionStatus::OPEN,
            'guest_count' => 2,
        ]);
        SessionTable::query()->create([
            'session_id' => $session->id,
            'table_id' => $table->id,
            'joined_at' => $session->opened_at,
        ]);
        $table->update(['status' => TableStatus::OCCUPIED]);

        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'session_id' => $session->id,
            'order_no' => 'KDS-DEMO-'.Str::upper(Str::random(8)),
            'channel' => OrderChannel::DINE_IN,
            'status' => OrderStatus::DRAFT,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
        ]);
        $batch = OrderBatch::query()->create([
            'order_id' => $order->id,
            'batch_no' => 1,
            'serve_mode' => ServeMode::TOGETHER,
            'status' => BatchStatus::OPEN,
        ]);

        $subtotal = 0;

        foreach ($demoOrder['items'] as $line) {
            $menuItem = $line['menu_item'];

            if ($menuItem === null) {
                continue;
            }

            $variant = isset($line['variant'])
                ? ItemVariant::query()->where('menu_item_id', $menuItem->id)->where('name', $line['variant'])->first()
                : null;
            $modifier = $line['modifier'] ?? null;
            $unitPrice = (int) $menuItem->base_price + (int) ($variant?->price_delta ?? 0) + (int) ($modifier?->price ?? 0);
            $lineTotal = $unitPrice * $line['quantity'];
            $subtotal += $lineTotal;

            $orderItem = OrderItem::query()->create([
                'order_id' => $order->id,
                'batch_id' => $batch->id,
                'menu_item_id' => $menuItem->id,
                'variant_id' => $variant?->id,
                'item_name_snapshot' => $menuItem->name,
                'variant_name_snapshot' => $variant?->name,
                'unit_price_snapshot' => $unitPrice,
                'quantity' => $line['quantity'],
                'line_total' => $lineTotal,
                'note' => $line['note'] ?? null,
                'status' => OrderItemStatus::PENDING,
            ]);

            if ($modifier !== null) {
                OrderItemModifier::query()->create([
                    'order_item_id' => $orderItem->id,
                    'modifier_id' => $modifier->id,
                    'name_snapshot' => $modifier->name,
                    'price_snapshot' => $modifier->price,
                    'quantity' => 1,
                ]);
            }
        }

        $order->update(['subtotal' => $subtotal, 'total_amount' => $subtotal]);

        // Gửi bếp đúng như luồng thật (MOD03 bấm "Gửi bếp"), rồi lùi giờ gửi để màn hình có thời gian chờ khác nhau.
        $tickets = app(KitchenTicketDispatcher::class)->sendBatch($batch);

        foreach ($tickets as $ticket) {
            $ticket->update(['fired_at' => $firedAt]);
            $ticket->items()->update(['fire_at' => $firedAt]);
        }

        $batch->update(['sent_at' => $firedAt]);
    }
}
