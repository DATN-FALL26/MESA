@extends('layouts.auth')

@section('title', 'Gọi món - Bàn ' . $session->table_code . ' | MESA')

@section('heading', 'Gọi món - Bàn ' . $session->table_code)

@section('content')

{{-- 1. Wrapper ngoài --}}
<div class="mx-auto w-full max-w-[1600px] space-y-5">

    {{-- =========================
        THÔNG TIN BÀN
    ========================== --}}
    <div class="mb-6">

        <a href="{{ route('tables.index') }}"
           class="text-sm font-medium text-blue-600 hover:underline">
            ← Quay lại danh sách bàn
        </a>

        <h1 class="mt-4 text-3xl font-bold text-slate-800">
            Bàn {{ $session->table_code }}
        </h1>

        <p class="mt-2 text-slate-500">
            {{ $session->guest_count }} khách
        </p>

        <p class="mt-1 text-sm text-slate-400">
            Dining Session #{{ $session->session_id }}
        </p>

    </div>


    {{-- =========================
        NẾU CHƯA CÓ ORDER
    ========================== --}}
    @if (!$order)

        <form
            action="{{ route('orders.start', $session->session_id) }}"
            method="POST"
            class="mb-6"
            data-order-ajax
        >
            @csrf

            <button
                type="submit"
                class="rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700"
            >
                Bắt đầu gọi món
            </button>
        </form>

    @endif


    {{-- =========================
        MENU + ORDER
    ========================== --}}
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_390px]">

        {{-- =========================
            MENU BÊN TRÁI
        ========================== --}}
        <div id="menu-panel" class="min-w-0">

            <h2 class="mb-4 text-xl font-bold text-slate-800">
                Thực đơn
            </h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">

                @foreach ($menuItems as $item)

                    <div class="overflow-hidden rounded-xl bg-white shadow">

                        {{-- Ảnh món --}}
                        @if ($item->image_path && file_exists(public_path($item->image_path)))

                            <img
                                src="{{ asset($item->image_path) }}"
                                alt="{{ $item->name }}"
                                class="h-40 w-full object-cover"
                            >

                        @else

                            <div class="flex h-40 w-full items-center justify-center bg-slate-100 text-sm text-slate-400">
                                Chưa có ảnh món
                            </div>

                        @endif


                        <div class="p-4">

                            <h3 class="text-lg font-bold text-slate-800">
                                {{ $item->name }}
                            </h3>

                            <p class="mt-2 text-sm text-slate-500">
                                {{ $item->description }}
                            </p>

                            <div class="mt-4 text-lg font-bold text-red-600">
                                {{ number_format($item->base_price, 0, ',', '.') }}đ
                            </div>


                            {{-- Nút thêm món --}}
                            @if ($order)

                                <form
                                    action="{{ route('orders.items.add', [$order->id, $item->id]) }}"
                                    method="POST"
                                    class="mt-4"
                                    data-order-ajax
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="w-full rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700"
                                    >
                                        + Thêm món
                                    </button>
                                </form>

                            @endif

                        </div>

                    </div>

                @endforeach

            </div>

        </div>


        {{-- =========================
            ORDER BÊN PHẢI
        ========================== --}}
        <div id="order-panel" class="min-w-0 xl:pt-11">

            @if ($order)

               <div class="sticky top-20 flex h-[calc(100vh-7rem)] min-h-[620px] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="border-b border-slate-200 pb-4">

                        <p class="text-sm text-slate-500">
                            Order hiện tại
                        </p>

                        <h2 class="mt-1 text-lg font-bold text-slate-800">
                            {{ $order->order_no }}
                        </h2>

                        @if ($order->status === 'draft')

                            <span class="mt-2 inline-block rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                Đang chọn món
                            </span>

                        @else

                            <span class="mt-2 inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                {{ $order->status }}
                            </span>

                        @endif

                    </div>


                    {{-- Danh sách món trong Order --}}
<div class="mt-4 flex-1 space-y-4 overflow-y-auto pr-2">

    @forelse ($orderItems as $orderItem)

        @php
    $isReadyMade = in_array(
        $orderItem->item_type,
        [
            \App\Enums\ItemType::DRINK->value,
            \App\Enums\ItemType::SIDE->value,
        ],
        true
    );

    $canEditQuantity =
        $orderItem->status === 'pending'
        ||
        $isReadyMade
        ||
        (
            $orderItem->status === 'sent'
            && $orderItem->kitchen_status === 'new'
        );
@endphp

        <div class="border-b border-slate-100 pb-4 last:border-b-0">

            {{-- Tên món + thành tiền --}}
            <div class="flex justify-between gap-4">

                <div class="font-medium text-slate-800">
                    {{ $orderItem->item_name_snapshot }}
                </div>

                <div class="font-semibold text-slate-800">
                    {{ number_format($orderItem->line_total, 0, ',', '.') }}đ
                </div>

            </div>


            {{-- Trạng thái món --}}
            <div class="mt-1">

                @if ($orderItem->status === 'pending')

                    <span class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-700">
                        Chưa gửi bếp
                    </span>

                @elseif ($orderItem->status === 'sent')

                    <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">
                        Đã gửi bếp
                    </span>

                @elseif ($orderItem->status === 'preparing')

                    <span class="rounded-full bg-orange-100 px-2 py-1 text-xs font-semibold text-orange-700">
                        Đang làm
                    </span>

                @elseif ($orderItem->status === 'ready')

                    <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">
                        Hoàn thành
                    </span>

                @endif

            </div>


            {{-- Số lượng --}}
            @if ($canEditQuantity)

                <div class="mt-3 flex items-center gap-2">

                    {{-- Giảm --}}
                    <form
                        action="{{ route('orders.items.decrease', [$order->id, $orderItem->id]) }}"
                        method="POST"
                        data-order-ajax
                    >
                        @csrf

                        <button
                            type="submit"
                            class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white font-bold text-slate-700 hover:bg-slate-100"
                        >
                            −
                        </button>
                    </form>


                    {{-- Số lượng hiện tại --}}
                    <span class="min-w-6 text-center text-sm font-semibold text-slate-700">
                        {{ $orderItem->quantity }}
                    </span>


                    {{-- Tăng --}}
                    <form
                        action="{{ route('orders.items.add', [$order->id, $orderItem->menu_item_id]) }}"
                        method="POST"
                        class="mt-4"
                        data-order-ajax
                    >
                        @csrf

                        <button
                            type="submit"
                            class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-600 font-bold text-white hover:bg-blue-700"
                        >
                            +
                        </button>
                    </form>


                    <span class="ml-1 text-sm text-slate-400">
                        {{ number_format($orderItem->unit_price_snapshot, 0, ',', '.') }}đ / món
                    </span>

                </div>

            @else

                <div class="mt-3 text-sm text-slate-500">
                    Số lượng:
                    <span class="font-semibold">
                        {{ $orderItem->quantity }}
                    </span>

                    · {{ number_format($orderItem->unit_price_snapshot, 0, ',', '.') }}đ / món
                </div>

            @endif


            {{-- Ghi chú --}}
            @if ($canEditQuantity)

                <form
                    action="{{ route('orders.items.note', [$order->id, $orderItem->id]) }}"
                    method="POST"
                    class="mt-3"
                    data-order-ajax
                >
                    @csrf

                    <input
                        type="text"
                        name="note"
                        value="{{ $orderItem->note }}"
                        placeholder="Ghi chú món..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500"
                    >

                    <button
                        type="submit"
                        class="mt-2 rounded-lg bg-slate-800 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-900"
                    >
                        Lưu ghi chú
                    </button>

                </form>

            @elseif ($orderItem->note)

                <div class="mt-2 text-sm text-slate-500">
                    Ghi chú: {{ $orderItem->note }}
                </div>

            @endif

        </div>

    @empty

        <div class="py-6 text-center text-sm text-slate-400">
            Chưa có món nào
        </div>

    @endforelse

</div>
<div class="shrink-0 border-t border-slate-200 bg-white pt-4">

                    {{-- Tổng tiền --}}
                    <div class="mt-6 border-t border-slate-200 pt-4">

                        <div class="flex items-center justify-between">

                            <span class="font-semibold text-slate-600">
                                Tổng cộng
                            </span>

                            <span class="text-xl font-bold text-red-600">
                                {{ number_format($order->total_amount, 0, ',', '.') }}đ
                            </span>

                        </div>

                    </div>
                    

</div>
{{-- NÚT GỬI BẾP --}}
                 @php
    $hasPending = $orderItems
        ->where('status', 'pending')
        ->count() > 0;

    $hasKitchenChanges = $orderItems->contains(function ($item) {

    if (!$item->kitchen_updated_at) {
        return false;
    }

    $isReadyMade = in_array(
        $item->item_type,
        [
            \App\Enums\ItemType::DRINK->value,
            \App\Enums\ItemType::SIDE->value,
        ],
        true
    );

    $canUpdateKitchen =
        $isReadyMade
        ||
        (
            $item->status === 'sent'
            && $item->kitchen_status === 'new'
        );

    if (!$canUpdateKitchen) {
        return false;
    }

    return strtotime($item->updated_at)
        > strtotime($item->kitchen_updated_at);
});
@endphp
@if ($hasPending)

    <form
        action="{{ route('orders.send-kitchen', $order->id) }}"
        method="POST"
        class="mt-4"
        data-order-ajax
    >
        @csrf

        <button
            type="submit"
            class="w-full rounded-xl bg-emerald-600 px-4 py-3 font-semibold text-white"
        >
            Gửi bếp
        </button>
    </form>

@elseif ($hasKitchenChanges)

    <form
        action="{{ route('orders.update-kitchen', $order->id) }}"
        method="POST"
        class="mt-4"
        data-order-ajax
    >
        @csrf

        <button
            type="submit"
            class="w-full rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white"
        >
            Cập nhật bếp
        </button>
    </form>

@else

    <button
        type="button"
        disabled
        class="mt-4 w-full cursor-not-allowed rounded-xl bg-slate-800 px-4 py-3 font-semibold text-white opacity-70"
    >
        Đã gửi bếp
    </button>

@endif

</div>

                </div>

            @endif

        </div>

    </div>



<script>
document.addEventListener('submit', async function (event) {

    /*
    |--------------------------------------------------------------------------
    | Chỉ xử lý những form có data-order-ajax
    |--------------------------------------------------------------------------
    */

    const form = event.target.closest('form[data-order-ajax]');

    if (!form) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Không cho browser submit theo cách bình thường
    |--------------------------------------------------------------------------
    |
    | Nếu không có dòng này thì trang vẫn reload.
    |
    */

    event.preventDefault();


    /*
    |--------------------------------------------------------------------------
    | Chống người dùng click liên tục nhiều lần
    |--------------------------------------------------------------------------
    */

    if (form.dataset.submitting === 'true') {
        return;
    }

    form.dataset.submitting = 'true';


    /*
    |--------------------------------------------------------------------------
    | Disable nút trong lúc request đang chạy
    |--------------------------------------------------------------------------
    */

    const button = form.querySelector('button[type="submit"]');

    if (button) {
        button.disabled = true;
        button.classList.add(
            'opacity-60',
            'cursor-wait'
        );
    }


    try {

        /*
        |--------------------------------------------------------------------------
        | Gửi form tới Laravel bằng Fetch
        |--------------------------------------------------------------------------
        |
        | FormData tự lấy:
        |
        | _token
        | note
        | và các field khác trong form
        |
        */

        const response = await fetch(form.action, {

            method: form.method || 'POST',

            body: new FormData(form),

            credentials: 'same-origin',

            redirect: 'follow',

            headers: {
                'Accept': 'text/html'
            }

        });


        /*
        |--------------------------------------------------------------------------
        | Nếu server báo lỗi
        |--------------------------------------------------------------------------
        */

        if (!response.ok) {

            throw new Error(
                'HTTP error: ' + response.status
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Laravel Controller hiện tại redirect về session
        |--------------------------------------------------------------------------
        |
        | Fetch sẽ nhận HTML mới nhưng browser KHÔNG reload.
        |
        */

        const html = await response.text();


        /*
        |--------------------------------------------------------------------------
        | Biến HTML nhận được thành DOM
        |--------------------------------------------------------------------------
        */

        const parser = new DOMParser();

        const newDocument = parser.parseFromString(
            html,
            'text/html'
        );


        /*
        |--------------------------------------------------------------------------
        | Lấy card Order mới từ HTML vừa nhận
        |--------------------------------------------------------------------------
        */

        /*
|--------------------------------------------------------------------------
| Cập nhật Menu bên trái
|--------------------------------------------------------------------------
*/

const newMenuPanel =
    newDocument.querySelector('#menu-panel');

const currentMenuPanel =
    document.querySelector('#menu-panel');

if (newMenuPanel && currentMenuPanel) {

    currentMenuPanel.replaceWith(
        newMenuPanel
    );

}


/*
|--------------------------------------------------------------------------
| Cập nhật Order bên phải
|--------------------------------------------------------------------------
*/

const newOrderPanel =
    newDocument.querySelector('#order-panel');

const currentOrderPanel =
    document.querySelector('#order-panel');

if (!newOrderPanel || !currentOrderPanel) {

    throw new Error(
        'Không tìm thấy #order-panel'
    );

}

currentOrderPanel.replaceWith(
    newOrderPanel
);


    } catch (error) {

        console.error(error);

        alert(
            'Có lỗi khi cập nhật Order. Vui lòng thử lại.'
        );

    } finally {

        /*
        |--------------------------------------------------------------------------
        | Mở lại nút
        |--------------------------------------------------------------------------
        |
        | Đặc biệt cần cho nút + Thêm món bên Menu,
        | vì Menu không bị render lại.
        |
        */

        form.dataset.submitting = 'false';

        if (button) {

            button.disabled = false;

            button.classList.remove(
                'opacity-60',
                'cursor-wait'
            );

        }

    }

});

/*
|--------------------------------------------------------------------------
| Realtime Kitchen -> Order
|--------------------------------------------------------------------------
*/

window.addEventListener('load', function () {

    if (!window.Echo) {
        console.error('Echo chưa được khởi tạo');
        return;
    }

    window.Echo
        .channel('orders.{{ $order->id }}')
        .listen('.kitchen.item.status.changed', async (event) => {

            console.log('Kitchen realtime:', event);

            try {

                const response = await fetch(window.location.href, {
                    headers: {
                        'Accept': 'text/html'
                    }
                });

                if (!response.ok) {
                    throw new Error('HTTP error: ' + response.status);
                }

                const html = await response.text();

                const parser = new DOMParser();
                const newDocument = parser.parseFromString(
                    html,
                    'text/html'
                );

                const newOrderPanel =
                    newDocument.querySelector('#order-panel');

                const currentOrderPanel =
                    document.querySelector('#order-panel');

                if (newOrderPanel && currentOrderPanel) {
                    currentOrderPanel.replaceWith(newOrderPanel);
                }

            } catch (error) {

                console.error(
                    'Realtime Order refresh error:',
                    error
                );

            }

        });

});
</script>

@endsection
