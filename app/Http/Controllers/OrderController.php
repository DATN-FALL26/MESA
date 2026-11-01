<?php

namespace App\Http\Controllers;

use App\Models\DiningTable;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        // Lấy danh sách bàn của chi nhánh 1
        $tables = DiningTable::where('branch_id', 1)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();

        // Lấy các bàn đang có Dining Session mở
        $occupiedTables = DB::table('session_tables')
            ->join(
                'dining_sessions',
                'session_tables.session_id',
                '=',
                'dining_sessions.id'
            )
            ->where('dining_sessions.branch_id', 1)
            ->where('dining_sessions.status', 'open')
            ->whereNull('session_tables.left_at')
            ->select(
                'session_tables.table_id',
                'dining_sessions.id as session_id',
                'dining_sessions.guest_count',
                'dining_sessions.opened_at'
            )
            ->get()
            ->keyBy('table_id');

        return view('orders.index', compact(
            'tables',
            'occupiedTables'
        ));
    }

    public function openTable($tableId)
{
    $table = DB::table('dining_tables')
        ->where('id', $tableId)
        ->where('branch_id', 1)
        ->where('is_active', 1)
        ->first();

    abort_if(!$table, 404);

    $sessionId = DB::transaction(function () use ($table) {

        // Khóa bàn để tránh 2 người cùng mở một bàn
        DB::table('dining_tables')
            ->where('id', $table->id)
            ->lockForUpdate()
            ->first();


        // Kiểm tra xem bàn đã có Dining Session mở chưa
        $existingSession = DB::table('session_tables')
            ->join(
                'dining_sessions',
                'session_tables.session_id',
                '=',
                'dining_sessions.id'
            )
            ->where('session_tables.table_id', $table->id)
            ->where('dining_sessions.status', 'open')
            ->whereNull('session_tables.left_at')
            ->select('dining_sessions.id')
            ->first();


        // Nếu bàn đã được mở rồi thì dùng lại Session cũ
        if ($existingSession) {
            return $existingSession->id;
        }


        // Tạo Dining Session mới
        $sessionId = DB::table('dining_sessions')->insertGetId([
            'branch_id'   => $table->branch_id,
            'opened_by'   => 1,
            'opened_at'   => now(),
            'closed_at'   => null,
            'guest_count' => 1,
            'status'      => 'open',
            'note'        => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);


        // Gắn bàn vào Dining Session
        DB::table('session_tables')->insert([
            'session_id' => $sessionId,
            'table_id'   => $table->id,
            'joined_at'  => now(),
            'left_at'    => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        return $sessionId;
    });


    // Đi vào màn gọi món của bàn vừa mở
    return redirect()->route(
        'orders.session',
        $sessionId
    );
}

    public function showSession($sessionId)
{
    $session = DB::table('dining_sessions')
        ->join(
            'session_tables',
            'dining_sessions.id',
            '=',
            'session_tables.session_id'
        )
        ->join(
            'dining_tables',
            'session_tables.table_id',
            '=',
            'dining_tables.id'
        )
        ->where('dining_sessions.id', $sessionId)
        ->where('dining_sessions.status', 'open')
        ->whereNull('session_tables.left_at')
        ->select(
            'dining_sessions.id as session_id',
            'dining_sessions.guest_count',
            'dining_sessions.opened_at',
            'dining_tables.id as table_id',
            'dining_tables.code as table_code',
            'dining_tables.seats'
        )
        ->first();

    abort_if(!$session, 404);
$menuItems = DB::table('menu_items')
    ->where('is_active', 1)
    ->orderBy('category_id')
    ->orderBy('name')
    ->get();

$order = DB::table('orders')
    ->where('session_id', $sessionId)
    ->whereNotIn('status', ['completed', 'cancelled'])
    ->first();

    $orderItems = collect();

if ($order) {
    $orderItems = DB::table('order_items')
        ->leftJoin(
            'kitchen_ticket_items',
            'order_items.id',
            '=',
            'kitchen_ticket_items.order_item_id'
        )
        ->where('order_items.order_id', $order->id)
        ->where('order_items.status', '!=', 'cancelled')
       ->select(
    'order_items.*',
    'kitchen_ticket_items.status as kitchen_status',
    'kitchen_ticket_items.updated_at as kitchen_updated_at'
)
        ->orderBy('order_items.id')
        ->get();
}

return view('orders.session', compact(
    'session',
    'menuItems',
    'order',
    'orderItems'
));


}

public function startOrder($sessionId)
{
    $session = DB::table('dining_sessions')
        ->where('id', $sessionId)
        ->where('status', 'open')
        ->first();

    abort_if(!$session, 404);

    $existingOrder = DB::table('orders')
        ->where('session_id', $sessionId)
        ->whereNotIn('status', ['completed', 'cancelled'])
        ->first();

    if ($existingOrder) {
        return redirect()->route('orders.session', $sessionId);
    }

    DB::table('orders')->insert([
        'branch_id'       => $session->branch_id,
        'session_id'      => $sessionId,
        'order_no'        => 'ORD-' . now()->format('YmdHis'),
        'channel'         => 'dine_in',
        'status'          => 'draft',
        'subtotal'        => 0,
        'discount_amount' => 0,
        'tax_amount'      => 0,
        'total_amount'    => 0,
        'created_by'      => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    return redirect()->route('orders.session', $sessionId);
}

public function addItem($orderId, $menuItemId)
{
    // Lấy Order hiện tại
    $order = DB::table('orders')
        ->where('id', $orderId)
        ->whereNotIn('status', ['completed', 'cancelled'])
        ->first();

    abort_if(!$order, 404);

    // Lấy món trong menu
    $menuItem = DB::table('menu_items')
        ->where('id', $menuItemId)
        ->where('is_active', 1)
        ->first();

    abort_if(!$menuItem, 404);

    DB::transaction(function () use ($order, $menuItem) {

        /*
        |--------------------------------------------------------------------------
        | 1. Ưu tiên tìm món pending
        |--------------------------------------------------------------------------
        | Nếu khách đang gọi thêm nhưng chưa gửi bếp,
        | chỉ tăng số lượng dòng pending đó.
        */
        $pendingItem = DB::table('order_items')
            ->where('order_id', $order->id)
            ->where('menu_item_id', $menuItem->id)
            ->where('status', 'pending')
            ->whereNull('batch_id')
            ->whereNull('variant_id')
            ->first();

        if ($pendingItem) {

            $newQuantity = $pendingItem->quantity + 1;

            DB::table('order_items')
                ->where('id', $pendingItem->id)
                ->update([
                    'quantity' => $newQuantity,
                    'line_total' => $pendingItem->unit_price_snapshot * $newQuantity,
                    'updated_at' => now(),
                ]);

        } else {

            /*
            |--------------------------------------------------------------------------
            | 2. Tìm món đã gửi nhưng bếp chưa làm
            |--------------------------------------------------------------------------
            */
            $sentItem = DB::table('order_items')
                ->join(
                    'kitchen_ticket_items',
                    'order_items.id',
                    '=',
                    'kitchen_ticket_items.order_item_id'
                )
                ->where('order_items.order_id', $order->id)
                ->where('order_items.menu_item_id', $menuItem->id)
                ->where('order_items.status', 'sent')
                ->where('kitchen_ticket_items.status', 'new')
                ->whereNull('order_items.variant_id')
                ->select('order_items.*')
                ->first();

            if ($sentItem) {

                // Bếp chưa bắt đầu → tăng trực tiếp Batch hiện tại
                $newQuantity = $sentItem->quantity + 1;

                DB::table('order_items')
                    ->where('id', $sentItem->id)
                    ->update([
                        'quantity' => $newQuantity,
                        'line_total' => $sentItem->unit_price_snapshot * $newQuantity,
                        'updated_at' => now(),
                    ]);

            } else {

                /*
                |--------------------------------------------------------------------------
                | 3. Không có pending và Batch cũ không sửa được
                |--------------------------------------------------------------------------
                | Tạo món mới để chờ Batch tiếp theo.
                */
                DB::table('order_items')->insert([
                    'order_id' => $order->id,
                    'batch_id' => null,
                    'menu_item_id' => $menuItem->id,
                    'variant_id' => null,
                    'item_name_snapshot' => $menuItem->name,
                    'variant_name_snapshot' => null,
                    'unit_price_snapshot' => $menuItem->base_price,
                    'quantity' => 1,
                    'line_total' => $menuItem->base_price,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Tính lại tổng tiền toàn Order
        $subtotal = DB::table('order_items')
            ->where('order_id', $order->id)
            ->where('status', '!=', 'cancelled')
            ->sum('line_total');

        DB::table('orders')
            ->where('id', $order->id)
            ->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'updated_at' => now(),
            ]);
    });

    return redirect()->route('orders.session', $order->session_id);
}

public function decreaseItem($orderId, $orderItemId)
{
    // Lấy Order hiện tại
    $order = DB::table('orders')
        ->where('id', $orderId)
        ->whereNotIn('status', ['completed', 'cancelled'])
        ->first();

    abort_if(!$order, 404);

    // Lấy món + trạng thái của món bên bếp
    $orderItem = DB::table('order_items')
        ->leftJoin(
            'kitchen_ticket_items',
            'order_items.id',
            '=',
            'kitchen_ticket_items.order_item_id'
        )
        ->where('order_items.id', $orderItemId)
        ->where('order_items.order_id', $orderId)
        ->select(
            'order_items.*',
            'kitchen_ticket_items.id as kitchen_item_id',
            'kitchen_ticket_items.status as kitchen_status'
        )
        ->first();

    abort_if(!$orderItem, 404);

    // Món được phép sửa khi:
    // 1. Chưa gửi bếp
    // 2. Đã gửi nhưng bếp vẫn chưa bắt đầu làm
    $canEdit =
        $orderItem->status === 'pending'
        ||
        (
            $orderItem->status === 'sent'
            && $orderItem->kitchen_status === 'new'
        );

    abort_if(!$canEdit, 403, 'Món đã được bếp bắt đầu làm, không thể giảm số lượng.');

    DB::transaction(function () use ($order, $orderItem) {

        if ($orderItem->quantity > 1) {

            // Giảm số lượng
            $newQuantity = $orderItem->quantity - 1;

            DB::table('order_items')
                ->where('id', $orderItem->id)
                ->update([
                    'quantity' => $newQuantity,
                    'line_total' => $orderItem->unit_price_snapshot * $newQuantity,
                    'updated_at' => now(),
                ]);

        } else {

            // Nếu món chưa gửi bếp → xóa luôn
            if ($orderItem->status === 'pending') {

                DB::table('order_items')
                    ->where('id', $orderItem->id)
                    ->delete();

            } else {

                // Đã gửi nhưng bếp chưa làm → hủy món
                DB::table('order_items')
                    ->where('id', $orderItem->id)
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_reason' => 'Hủy trước khi bếp bắt đầu làm',
                        'cancelled_at' => now(),
                        'updated_at' => now(),
                    ]);

                DB::table('kitchen_ticket_items')
                    ->where('id', $orderItem->kitchen_item_id)
                    ->update([
                        'status' => 'cancelled',
                        'updated_at' => now(),
                    ]);
            }
        }

        // Tính lại tổng tiền Order
        $subtotal = DB::table('order_items')
            ->where('order_id', $order->id)
            ->where('status', '!=', 'cancelled')
            ->sum('line_total');

        DB::table('orders')
            ->where('id', $order->id)
            ->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'updated_at' => now(),
            ]);
    });

    return redirect()->route('orders.session', $order->session_id);
}

public function updateItemNote($orderId, $orderItemId)
{
    $order = DB::table('orders')
        ->where('id', $orderId)
        ->whereNotIn('status', ['completed', 'cancelled'])
        ->first();

    abort_if(!$order, 404);

    $orderItem = DB::table('order_items')
        ->where('id', $orderItemId)
        ->where('order_id', $orderId)
        ->where('status', 'pending')
        ->whereNull('batch_id')
        ->first();

    abort_if(!$orderItem, 404);

    DB::table('order_items')
        ->where('id', $orderItemId)
        ->update([
            'note' => request('note'),
            'updated_at' => now(),
        ]);

    return redirect()->route('orders.session', $order->session_id);
}

public function sendToKitchen($orderId)
{
    // Lấy Order hiện tại
    $order = DB::table('orders')
        ->where('id', $orderId)
        ->whereNotIn('status', ['completed', 'cancelled'])
        ->first();

    abort_if(!$order, 404);

    // Chỉ lấy những món chưa gửi bếp
    $pendingItems = DB::table('order_items')
        ->join(
            'menu_items',
            'order_items.menu_item_id',
            '=',
            'menu_items.id'
        )
        ->where('order_items.order_id', $orderId)
        ->where('order_items.status', 'pending')
        ->whereNull('order_items.batch_id')
        ->select(
            'order_items.*',
            'menu_items.station_code'
        )
        ->get();

    // Không có món mới thì không tạo batch
    if ($pendingItems->isEmpty()) {
        return redirect()
            ->route('orders.session', $order->session_id)
            ->with('error', 'Không có món mới để gửi bếp.');
    }

    DB::transaction(function () use ($order, $pendingItems) {

        // Tìm số batch tiếp theo
        $lastBatchNo = DB::table('order_batches')
            ->where('order_id', $order->id)
            ->max('batch_no');

        $batchNo = ($lastBatchNo ?? 0) + 1;

        // Tạo một đợt gửi bếp mới
        $batchId = DB::table('order_batches')->insertGetId([
            'order_id'    => $order->id,
            'batch_no'    => $batchNo,
            'serve_mode'  => 'as_ready',
            'status'      => 'sent',
            'sent_at'     => now(),
            'released_by' => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Chia món theo khu bếp: PHO / DRINK / SIDE
        $itemsByStation = $pendingItems->groupBy('station_code');

        foreach ($itemsByStation as $stationCode => $items) {

            // Tìm station đúng chi nhánh
            $station = DB::table('stations')
                ->where('branch_id', $order->branch_id)
                ->where('station_code', $stationCode)
                ->where('is_active', 1)
                ->first();

            if (!$station) {
                throw new \RuntimeException(
                    "Không tìm thấy station: {$stationCode}"
                );
            }

            // Tạo phiếu bếp
            $ticketId = DB::table('kitchen_tickets')->insertGetId([
                'branch_id' => $order->branch_id,
                'order_id'  => $order->id,
                'batch_id'  => $batchId,
                'station_id' => $station->id,
                'ticket_no' => 'KT-' . $order->id . '-' . $batchNo . '-' . $stationCode,
                'status'     => 'new',
                'fired_at'   => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Đưa từng món vào phiếu bếp
            foreach ($items as $item) {

                DB::table('kitchen_ticket_items')->insert([
                    'ticket_id'     => $ticketId,
                    'order_item_id' => $item->id,
                    'status'        => 'new',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                // Đánh dấu món đã gửi bếp
                DB::table('order_items')
                    ->where('id', $item->id)
                    ->update([
                        'batch_id'   => $batchId,
                        'status'     => 'sent',
                        'updated_at' => now(),
                    ]);
            }
        }

        // Order đã được gửi bếp
        if ($order->status === 'draft') {
            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'status'     => 'sent',
                    'updated_at' => now(),
                ]);
        }
    });

    return redirect()->route('orders.session', $order->session_id);
}

public function updateKitchen($orderId)
{
    $order = DB::table('orders')
        ->where('id', $orderId)
        ->whereNotIn('status', ['completed', 'cancelled'])
        ->first();

    abort_if(!$order, 404);

    $changedItems = DB::table('order_items')
        ->join(
            'kitchen_ticket_items',
            'order_items.id',
            '=',
            'kitchen_ticket_items.order_item_id'
        )
        ->where('order_items.order_id', $orderId)
        ->where('order_items.status', 'sent')
        ->where('kitchen_ticket_items.status', 'new')
        ->whereColumn(
            'order_items.updated_at',
            '>',
            'kitchen_ticket_items.updated_at'
        )
        ->select(
            'kitchen_ticket_items.id as kitchen_item_id'
        )
        ->get();

    foreach ($changedItems as $item) {

        DB::table('kitchen_ticket_items')
            ->where('id', $item->kitchen_item_id)
            ->update([
                'updated_at' => now(),
            ]);
    }

    return redirect()->route(
        'orders.session',
        $order->session_id
    );
}

}

