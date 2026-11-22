@extends('layouts.order')

@section('title', 'Quản lý gọi món')

@section('content')

<div class="p-6">

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-800">
            Quản lý gọi món
        </h1>

        <p class="mt-1 text-slate-500">
            Chọn bàn để bắt đầu gọi món.
        </p>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-6">

        @foreach ($tables as $table)

            @php
                $session = $occupiedTables->get($table->id);
            @endphp

            @if ($session)

                <a href="{{ route('orders.session', $session->session_id) }}"
                   class="block rounded-xl bg-white p-5 shadow transition hover:-translate-y-1 hover:shadow-lg">

                    <div class="text-xl font-bold text-slate-800">
                        Bàn {{ $table->code }}
                    </div>

                    <div class="mt-2 text-sm text-slate-500">
                        {{ $table->seats }} chỗ ngồi
                    </div>

                    <div class="mt-3">
                        <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
                            Đang có khách
                        </span>

                        <div class="mt-2 text-sm text-slate-500">
                            {{ $session->guest_count }} khách
                        </div>
                    </div>

                </a>

            @else

    <form
        action="{{ route('orders.table.open', $table->id) }}"
        method="POST"
    >
        @csrf

        <button
            type="submit"
            class="w-full rounded-xl bg-white p-5 text-left shadow transition hover:-translate-y-1 hover:shadow-lg"
        >

            <div class="text-xl font-bold text-slate-800">
                Bàn {{ $table->code }}
            </div>

            <div class="mt-2 text-sm text-slate-500">
                {{ $table->seats }} chỗ ngồi
            </div>

            <div class="mt-3">
                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                    Trống
                </span>

                <div class="mt-3 text-sm font-medium text-blue-600">
                    Nhấn để mở bàn →
                </div>
            </div>

        </button>

    </form>

@endif

        @endforeach

    </div>

</div>

@endsection