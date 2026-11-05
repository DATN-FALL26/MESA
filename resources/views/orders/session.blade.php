@extends('layouts.auth')

@section('title', 'Gọi món - Bàn ' . $session->table_code . ' | MESA')

@section('heading', 'Gọi món - Bàn ' . $session->table_code)

@section('content')

<div class="mx-auto w-full max-w-[1600px] space-y-4 sm:space-y-6">

    {{-- =========================================================
        HEADER / THÔNG TIN BÀN
    ========================================================== --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">

        <div class="space-y-3 sm:space-y-4">

            <a
                href="{{ route('tables.index') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition hover:text-blue-700"
            >
                <span>←</span>
                <span>Quay lại danh sách bàn</span>
            </a>

            <div>

                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Điểm bán hàng
                </span>

                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                    Gọi món - Bàn {{ $session->table_code }}
                </h1>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Chọn món, cập nhật số lượng và theo dõi trạng thái món theo thời gian thực.
                </p>

            </div>

            <div class="flex flex-wrap items-center gap-2">

                <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm sm:text-sm">
                    {{ $session->guest_count }} khách
                </span>

                <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm sm:text-sm">
                    Dining Session #{{ $session->session_id }}
                </span>

            </div>

        </div>


        @if ($order)

            <div class="hidden rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm xl:block">

                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    Order hiện tại
                </p>

                <p class="mt-1 text-sm font-bold text-slate-800">
                    {{ $order->order_no }}
                </p>

            </div>

        @endif

    </div>


    {{-- =========================================================
        CHƯA CÓ ORDER
    ========================================================== --}}
    @if (!$order)

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:rounded-3xl sm:p-6">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <h2 class="text-base font-bold text-slate-900 sm:text-lg">
                        Bàn này chưa có order
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        Bắt đầu order để thêm món cho bàn {{ $session->table_code }}.
                    </p>

                </div>

                <form
                    action="{{ route('orders.start', $session->session_id) }}"
                    method="POST"
                    data-order-ajax
                >
                    @csrf

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 sm:w-auto"
                    >
                        Bắt đầu gọi món
                    </button>

                </form>

            </div>

        </div>

    @endif


    {{-- =========================================================
        MENU + ORDER
    ========================================================== --}}
    <div class="overflow-visible rounded-2xl border border-slate-200 bg-white shadow-sm sm:rounded-3xl">

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_420px]">


            {{-- =================================================
                MENU BÊN TRÁI
            ================================================== --}}
            <div
                id="menu-panel"
                class="min-w-0 border-b border-slate-200 p-4 sm:p-6 xl:border-b-0 xl:border-r"
            >

                {{-- Header menu --}}
                <div class="mb-4 flex flex-col gap-3 sm:mb-6 sm:flex-row sm:items-end sm:justify-between">

                    <div>

                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                            Danh sách món
                        </p>

                        <h2 class="mt-1 text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">
                            Thực đơn
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Chọn món để thêm vào order của bàn hiện tại.
                        </p>

                    </div>

                    <div class="w-fit rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                        {{ $menuItems->count() }} món
                    </div>

                </div>


                {{-- GRID MÓN --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-3">

                    @foreach ($menuItems as $item)

                        <div class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">

                            {{-- Ảnh món --}}
                            <div class="overflow-hidden bg-slate-100">

                                @if ($item->image_path && file_exists(public_path($item->image_path)))

                                    <img
                                        src="{{ asset($item->image_path) }}"
                                        alt="{{ $item->name }}"
                                        class="h-40 w-full object-cover transition duration-300 group-hover:scale-[1.02] sm:h-48"
                                    >

                                @else

                                    <div class="flex h-40 w-full items-center justify-center text-sm font-medium text-slate-400 sm:h-48">
                                        Chưa có ảnh món
                                    </div>

                                @endif

                            </div>


                            {{-- Nội dung món --}}
                            <div class="flex flex-col p-4 sm:min-h-[255px] sm:p-5">

                                <div class="flex-1">

                                    <h3 class="text-base font-bold text-slate-900 sm:text-lg">
                                        {{ $item->name }}
                                    </h3>

                                    <p class="mt-1.5 text-sm leading-5 text-slate-500 sm:mt-2 sm:min-h-[44px] sm:leading-6">
                                        {{ $item->description }}
                                    </p>

                                    <div class="mt-3 text-lg font-bold text-red-600 sm:mt-4 sm:text-xl">
                                        {{ number_format($item->base_price, 0, ',', '.') }}đ
                                    </div>

                                </div>


                                @if ($order)

                                    <form
                                        action="{{ route('orders.items.add', [$order->id, $item->id]) }}"
                                        method="POST"
                                        class="mt-4 sm:mt-5"
                                        data-order-ajax
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
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


            {{-- =================================================
                ORDER BÊN PHẢI
            ================================================== --}}
            <div
                id="order-panel"
                data-order-id="{{ $order?->id }}"
                class="min-w-0 bg-slate-50/70 p-3 sm:p-5 xl:p-6"
            >

                @if ($order)

                    {{-- Mobile: không sticky, không ép chiều cao.
                         Desktop XL: sticky + scroll riêng trong danh sách món. --}}
                    <div class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:sticky xl:top-20 xl:h-[calc(100vh-7rem)] xl:min-h-[620px]">


                        {{-- =====================================
                            HEADER ORDER
                        ====================================== --}}
                        <div class="shrink-0 border-b border-slate-200 px-4 py-4 sm:px-5 sm:py-5">

                            <div class="flex flex-wrap items-start justify-between gap-3">

                                <div class="min-w-0">

                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">
                                        Order hiện tại
                                    </p>

                                    <h2 class="mt-1 truncate text-lg font-bold text-slate-900 sm:text-xl">
                                        {{ $order->order_no }}
                                    </h2>

                                </div>


                                @if ($order->status === 'draft')

                                    <span class="inline-flex shrink-0 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                        Đang chọn món
                                    </span>

                                @else

                                    <span class="inline-flex shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                        {{ $order->status }}
                                    </span>

                                @endif

                            </div>

                        </div>


                        {{-- =====================================
                            DANH SÁCH MÓN
                        ====================================== --}}
                        <div class="space-y-3 px-3 py-3 sm:px-4 sm:py-4 xl:flex-1 xl:overflow-y-auto">

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


                                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:rounded-2xl sm:p-4">


                                    {{-- TÊN + GIÁ --}}
                                    <div class="flex items-start justify-between gap-3">

                                        <div class="min-w-0 flex-1">

                                            <div class="break-words text-sm font-semibold leading-5 text-slate-900 sm:text-base sm:leading-6">
                                                {{ $orderItem->item_name_snapshot }}
                                            </div>


                                            {{-- Trạng thái --}}
                                            <div class="mt-2">

                                                @if ($orderItem->status === 'pending')

                                                    <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-700">
                                                        Chưa gửi bếp
                                                    </span>

                                                @elseif ($orderItem->status === 'sent')

                                                    <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                        Đã gửi bếp
                                                    </span>

                                                @elseif ($orderItem->status === 'preparing')

                                                    <span class="inline-flex rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700">
                                                        Đang làm
                                                    </span>

                                                @elseif ($orderItem->status === 'ready')

                                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                                        Hoàn thành
                                                    </span>

                                                @endif

                                            </div>

                                        </div>


                                        <div class="shrink-0 text-right">

                                            <div class="text-sm font-bold text-slate-900 sm:text-base">
                                                {{ number_format($orderItem->line_total, 0, ',', '.') }}đ
                                            </div>

                                        </div>

                                    </div>


                                    {{-- =================================
                                        SỐ LƯỢNG
                                    ================================== --}}
                                    @if ($canEditQuantity)

                                        <div class="mt-4 flex flex-wrap items-center gap-2">

                                            {{-- Giảm --}}
                                            <form
                                                action="{{ route('orders.items.decrease', [$order->id, $orderItem->id]) }}"
                                                method="POST"
                                                data-order-ajax
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 bg-white text-base font-bold text-slate-700 transition hover:bg-slate-100"
                                                >
                                                    −
                                                </button>

                                            </form>


                                            {{-- Số lượng --}}
                                            <span class="min-w-8 text-center text-sm font-bold text-slate-800">
                                                {{ $orderItem->quantity }}
                                            </span>


                                            {{-- Tăng --}}
                                            <form
                                                action="{{ route('orders.items.add', [$order->id, $orderItem->menu_item_id]) }}"
                                                method="POST"
                                                data-order-ajax
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-base font-bold text-white transition hover:bg-blue-700"
                                                >
                                                    +
                                                </button>

                                            </form>


                                            <span class="w-full pt-1 text-xs text-slate-400 sm:ml-1 sm:w-auto sm:pt-0 sm:text-sm">
                                                {{ number_format($orderItem->unit_price_snapshot, 0, ',', '.') }}đ / món
                                            </span>

                                        </div>

                                    @else

                                        <div class="mt-3 text-sm leading-6 text-slate-500">

                                            Số lượng:

                                            <span class="font-semibold text-slate-700">
                                                {{ $orderItem->quantity }}
                                            </span>

                                            <span class="hidden sm:inline">·</span>

                                            <span class="block sm:inline">
                                                {{ number_format($orderItem->unit_price_snapshot, 0, ',', '.') }}đ / món
                                            </span>

                                        </div>

                                    @endif


                                    {{-- =================================
                                        GHI CHÚ
                                    ================================== --}}
                                    @if ($canEditQuantity)

                                        <form
                                            action="{{ route('orders.items.note', [$order->id, $orderItem->id]) }}"
                                            method="POST"
                                            class="mt-4"
                                            data-order-ajax
                                        >
                                            @csrf

                                            <input
                                                type="text"
                                                name="note"
                                                value="{{ $orderItem->note }}"
                                                placeholder="Ghi chú món..."
                                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                            >

                                            <button
                                                type="submit"
                                                class="mt-2 inline-flex min-h-10 w-full items-center justify-center rounded-xl bg-slate-800 px-3.5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-900 sm:w-auto"
                                            >
                                                Lưu ghi chú
                                            </button>

                                        </form>


                                    @elseif ($orderItem->note)

                                        <div class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm leading-6 text-slate-500">

                                            <span class="font-medium text-slate-600">
                                                Ghi chú:
                                            </span>

                                            {{ $orderItem->note }}

                                        </div>

                                    @endif

                                </div>


                            @empty

                                <div class="flex min-h-[150px] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 text-center text-sm text-slate-400">
                                    Chưa có món nào trong order.
                                </div>

                            @endforelse

                        </div>


                        {{-- =====================================
                            FOOTER ORDER
                        ====================================== --}}
                        <div class="shrink-0 border-t border-slate-200 bg-white p-3 sm:p-4">


                            {{-- Tổng tiền --}}
                            <div class="rounded-xl bg-slate-50 p-3 sm:rounded-2xl sm:p-4">

                                <div class="flex items-center justify-between gap-3">

                                    <span class="text-sm font-semibold text-slate-500">
                                        Tổng cộng
                                    </span>

                                    <span class="text-xl font-bold tracking-tight text-red-600 sm:text-2xl">
                                        {{ number_format($order->total_amount, 0, ',', '.') }}đ
                                    </span>

                                </div>

                            </div>


                            {{-- =================================
                                KIỂM TRA NÚT GỬI / CẬP NHẬT BẾP
                            ================================== --}}
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
                                    class="mt-3"
                                    data-order-ajax
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                    >
                                        Gửi bếp
                                    </button>

                                </form>


                            @elseif ($hasKitchenChanges)

                                <form
                                    action="{{ route('orders.update-kitchen', $order->id) }}"
                                    method="POST"
                                    class="mt-3"
                                    data-order-ajax
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"
                                    >
                                        Cập nhật bếp
                                    </button>

                                </form>


                            @else

                                <button
                                    type="button"
                                    disabled
                                    class="mt-3 min-h-12 w-full cursor-not-allowed rounded-xl bg-slate-800 px-4 py-3 text-sm font-semibold text-white opacity-70"
                                >
                                    Đã gửi bếp
                                </button>

                            @endif

                        </div>

                    </div>


                @else

                    {{-- =========================================
                        CHƯA CÓ ORDER - PANEL PHẢI
                    ========================================== --}}
                    <div class="flex min-h-[260px] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-5 text-center sm:min-h-[420px] sm:px-6">

                        <div>

                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-400">
                                +
                            </div>

                            <h3 class="mt-4 text-base font-bold text-slate-800">
                                Chưa có order
                            </h3>

                            <p class="mt-1 max-w-xs text-sm leading-6 text-slate-500">
                                Bắt đầu gọi món để tạo order cho bàn này.
                            </p>

                        </div>

                    </div>

                @endif

            </div>

        </div>

    </div>

</div>


{{-- =============================================================
    AJAX + REALTIME
============================================================= --}}
<script>

/*
|--------------------------------------------------------------------------
| Realtime subscription hiện tại
|--------------------------------------------------------------------------
|
| Dùng biến này để tránh subscribe trùng channel khi AJAX render lại panel.
|
*/

let currentRealtimeOrderId = null;


/*
|--------------------------------------------------------------------------
| Refresh riêng Order Panel
|--------------------------------------------------------------------------
*/

async function refreshOrderPanel() {

    const response = await fetch(window.location.href, {
        credentials: 'same-origin',
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


    if (!newOrderPanel || !currentOrderPanel) {
        throw new Error('Không tìm thấy #order-panel');
    }


    currentOrderPanel.replaceWith(
        newOrderPanel
    );


    /*
    |--------------------------------------------------------------------------
    | Nếu AJAX vừa tạo order mới thì subscribe realtime luôn
    |--------------------------------------------------------------------------
    */

    subscribeOrderRealtime();
}


/*
|--------------------------------------------------------------------------
| Subscribe Reverb cho Order hiện tại
|--------------------------------------------------------------------------
*/

function subscribeOrderRealtime() {

    if (!window.Echo) {
        return;
    }


    const orderPanel =
        document.querySelector('#order-panel');


    if (!orderPanel) {
        return;
    }


    const orderId =
        orderPanel.dataset.orderId;


    if (!orderId) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Đã subscribe đúng order rồi thì không làm gì thêm
    |--------------------------------------------------------------------------
    */

    if (String(currentRealtimeOrderId) === String(orderId)) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Nếu chuyển sang order khác thì rời channel cũ
    |--------------------------------------------------------------------------
    */

    if (currentRealtimeOrderId) {

        window.Echo.leave(
            'orders.' + currentRealtimeOrderId
        );

    }


    currentRealtimeOrderId = orderId;


    window.Echo
        .channel('orders.' + orderId)
        .listen(
            '.kitchen.item.status.changed',

            async (event) => {

                console.log(
                    'Kitchen realtime:',
                    event
                );


                try {

                    await refreshOrderPanel();

                } catch (error) {

                    console.error(
                        'Realtime Order refresh error:',
                        error
                    );

                }

            }
        );
}


/*
|--------------------------------------------------------------------------
| AJAX ORDER
|--------------------------------------------------------------------------
*/

document.addEventListener('submit', async function (event) {

    const form =
        event.target.closest(
            'form[data-order-ajax]'
        );


    if (!form) {
        return;
    }


    event.preventDefault();


    /*
    |--------------------------------------------------------------------------
    | Chống click liên tục
    |--------------------------------------------------------------------------
    */

    if (form.dataset.submitting === 'true') {
        return;
    }


    form.dataset.submitting = 'true';


    const button =
        form.querySelector(
            'button[type="submit"]'
        );


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
        | Gửi form
        |--------------------------------------------------------------------------
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


        if (!response.ok) {

            throw new Error(
                'HTTP error: ' + response.status
            );

        }


        const html =
            await response.text();


        const parser =
            new DOMParser();


        const newDocument =
            parser.parseFromString(
                html,
                'text/html'
            );


        /*
        |--------------------------------------------------------------------------
        | Cập nhật Menu
        |--------------------------------------------------------------------------
        */

        const newMenuPanel =
            newDocument.querySelector(
                '#menu-panel'
            );


        const currentMenuPanel =
            document.querySelector(
                '#menu-panel'
            );


        if (newMenuPanel && currentMenuPanel) {

            currentMenuPanel.replaceWith(
                newMenuPanel
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Cập nhật Order
        |--------------------------------------------------------------------------
        */

        const newOrderPanel =
            newDocument.querySelector(
                '#order-panel'
            );


        const currentOrderPanel =
            document.querySelector(
                '#order-panel'
            );


        if (!newOrderPanel || !currentOrderPanel) {

            throw new Error(
                'Không tìm thấy #order-panel'
            );

        }


        currentOrderPanel.replaceWith(
            newOrderPanel
        );


        /*
        |--------------------------------------------------------------------------
        | Nếu vừa tạo order mới thì tự subscribe WebSocket
        |--------------------------------------------------------------------------
        */

        subscribeOrderRealtime();


    } catch (error) {

        console.error(error);

        alert(
            'Có lỗi khi cập nhật Order. Vui lòng thử lại.'
        );


    } finally {

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
| Khởi tạo realtime sau khi trang + Echo load xong
|--------------------------------------------------------------------------
*/

window.addEventListener('load', function () {

    if (!window.Echo) {

        console.error(
            'Echo chưa được khởi tạo'
        );

        return;

    }


    subscribeOrderRealtime();

});

</script>

@endsection
