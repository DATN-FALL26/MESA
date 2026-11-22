<?php

namespace App\Http\Controllers;

use App\Models\DiningTable;
use Illuminate\Support\Facades\DB;
use App\Models\OrderBatch;
use App\Services\Kitchen\KitchenTicketDispatcher;
use App\Enums\ItemType;

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
        ->leftJoin(
    'menu_items',
    'order_items.menu_item_id',
    '=',
    'menu_items.id'
)
        ->where('order_items.order_id', $order->id)
        ->where('order_items.status', '!=', 'cancelled')
       ->select(
    'order_items.*',
    'kitchen_ticket_items.status as kitchen_status',
    'kitchen_ticket_items.updated_at as kitchen_updated_at',
    'menu_items.item_type'
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
$isReadyMade = in_array(
    $menuItem->item_type,
    [
        ItemType::DRINK->value,
        ItemType::SIDE->value,
    ],
    true
);

    DB::transaction(function () use ($order, $menuItem, $isReadyMade) {

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
           $editableBatchItem = DB::table('order_items')
    ->leftJoin(
        'kitchen_ticket_items',
        'order_items.id',
        '=',
        'kitchen_ticket_items.order_item_id'
    )
    ->where('order_items.order_id', $order->id)
    ->where('order_items.menu_item_id', $menuItem->id)
    ->whereNull('order_items.variant_id')
    ->whereNotNull('order_items.batch_id')
    ->when(
        $isReadyMade,
        function ($query) {
            // Nước / quẩy / SIDE:
            // dù đã gửi, đang làm hay hoàn thành vẫn sửa batch hiện tại
            $query->whereIn('order_items.status', [
                'sent',
                'preparing',
                'ready',
            ]);
        },
        function ($query) {
            // Món phải chế biến:
            // chỉ sửa khi đã gửi nhưng bếp chưa bắt đầu
            $query
                ->where('order_items.status', 'sent')
                ->where('kitchen_ticket_items.status', 'new');
        }
    )
    ->select('order_items.*')
    ->orderByDesc('order_items.id')
    ->first();

if ($editableBatchItem) {
                // Bếp chưa bắt đầu → tăng trực tiếp Batch hiện tại
                $newQuantity = $editableBatchItem->quantity + 1;

                DB::table('order_items')
                    ->where('id', $editableBatchItem->id)
                    ->update([
                        'quantity' => $newQuantity,
                        'line_total' => $editableBatchItem->unit_price_snapshot * $newQuantity,
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
        ->leftJoin(
    'menu_items',
    'order_items.menu_item_id',
    '=',
    'menu_items.id'
)
        ->where('order_items.id', $orderItemId)
        ->where('order_items.order_id', $orderId)
        ->select(
    'order_items.*',
    'kitchen_ticket_items.id as kitchen_item_id',
    'kitchen_ticket_items.status as kitchen_status',
    'menu_items.item_type'
)
        ->first();

    abort_if(!$orderItem, 404);
    $isReadyMade = in_array(
    $orderItem->item_type,
    [
        ItemType::DRINK->value,
        ItemType::SIDE->value,
    ],
    true
);

    // Món được phép sửa khi:
    // 1. Chưa gửi bếp
    // 2. Đã gửi nhưng bếp vẫn chưa bắt đầu làm
    $canEdit =
    $orderItem->status === 'pending'
    ||
    $isReadyMade
    ||
    (
        $orderItem->status === 'sent'
        && $orderItem->kitchen_status === 'new'
    );

    abort_if(!$canEdit, 403, 'Món đã được bếp bắt đầu làm, không thể giảm số lượng.');

    DB::transaction(function () use ($order, $orderItem, $isReadyMade) {

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

    $cancelReason = $isReadyMade
        ? 'Khách trả hoặc hủy món có sẵn'
        : 'Hủy trước khi bếp bắt đầu làm';

    DB::table('order_items')
        ->where('id', $orderItem->id)
        ->update([
            'status' => 'cancelled',
            'cancelled_reason' => $cancelReason,
            'cancelled_at' => now(),
            'updated_at' => now(),
        ]);

    if ($orderItem->kitchen_item_id) {
        DB::table('kitchen_ticket_items')
            ->where('id', $orderItem->kitchen_item_id)
            ->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);
    }
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
    ->leftJoin(
        'kitchen_ticket_items',
        'order_items.id',
        '=',
        'kitchen_ticket_items.order_item_id'
    )
    ->leftJoin(
        'menu_items',
        'order_items.menu_item_id',
        '=',
        'menu_items.id'
    )
    ->where('order_items.id', $orderItemId)
    ->where('order_items.order_id', $orderId)
    ->select(
        'order_items.*',
        'kitchen_ticket_items.status as kitchen_status',
        'menu_items.item_type'
    )
    ->first();

abort_if(!$orderItem, 404);
$isReadyMade = in_array(
    $orderItem->item_type,
    [
        ItemType::DRINK->value,
        ItemType::SIDE->value,
    ],
    true
);

$canEdit =
    $orderItem->status === 'pending'
    ||
    $isReadyMade
    ||
    (
        $orderItem->status === 'sent'
        && $orderItem->kitchen_status === 'new'
    );

abort_if(
    !$canEdit,
    403,
    'Món đã được bếp bắt đầu chế biến, không thể sửa ghi chú.'
);

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
    /*
    |--------------------------------------------------------------------------
    | Lấy Order hiện tại
    |--------------------------------------------------------------------------
    */
    $order = DB::table('orders')
        ->where('id', $orderId)
        ->whereNotIn('status', ['completed', 'cancelled'])
        ->first();

    abort_if(!$order, 404);


    /*
    |--------------------------------------------------------------------------
    | Chỉ lấy những món pending chưa thuộc Batch
    |--------------------------------------------------------------------------
    */
    $pendingItems = DB::table('order_items')
        ->where('order_id', $orderId)
        ->where('status', 'pending')
        ->whereNull('batch_id')
        ->get();


    if ($pendingItems->isEmpty()) {
        return redirect()
            ->route('orders.session', $order->session_id)
            ->with('error', 'Không có món mới để gửi bếp.');
    }


    DB::transaction(function () use ($order, $pendingItems) {

        /*
        |--------------------------------------------------------------------------
        | Tìm số Batch tiếp theo
        |--------------------------------------------------------------------------
        */
        $lastBatchNo = OrderBatch::query()
            ->where('order_id', $order->id)
            ->max('batch_no');

        $batchNo = ($lastBatchNo ?? 0) + 1;


        /*
        |--------------------------------------------------------------------------
        | Tạo Batch mới
        |--------------------------------------------------------------------------
        |
        | Chưa đánh dấu SENT ở đây.
        | KitchenTicketDispatcher sẽ làm việc đó sau.
        |
        */
        $batch = OrderBatch::query()->create([
            'order_id' => $order->id,
            'batch_no' => $batchNo,
            'serve_mode' => 'as_ready',
            'status' => 'open',
            'sent_at' => null,
            'released_by' => auth()->id(),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Gắn các món pending vào Batch
        |--------------------------------------------------------------------------
        |
        | Vẫn giữ status = pending.
        | Dispatcher sẽ tự chuyển:
        |
        | pending → sent
        | hoặc pending → preparing với món có sẵn
        |
        */
        DB::table('order_items')
            ->whereIn(
                'id',
                $pendingItems->pluck('id')->all()
            )
            ->update([
                'batch_id' => $batch->id,
                'updated_at' => now(),
            ]);


        /*
        |--------------------------------------------------------------------------
        | Gửi Batch sang Module Bếp
        |--------------------------------------------------------------------------
        */
        app(KitchenTicketDispatcher::class)
            ->sendBatch(
                $batch,
                auth()->user()
            );
    });


    return redirect()->route(
        'orders.session',
        $order->session_id
    );
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
    ->join(
        'menu_items',
        'order_items.menu_item_id',
        '=',
        'menu_items.id'
    )
    ->where('order_items.order_id', $orderId)
    ->where('order_items.status', '!=', 'cancelled')
    ->whereColumn(
        'order_items.updated_at',
        '>',
        'kitchen_ticket_items.updated_at'
    )
    ->where(function ($query) {
        $query
            ->whereIn('menu_items.item_type', [
                ItemType::DRINK->value,
                ItemType::SIDE->value,
            ])
            ->orWhere(function ($query) {
                $query
                    ->where('order_items.status', 'sent')
                    ->where('kitchen_ticket_items.status', 'new');
            });
    })
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

