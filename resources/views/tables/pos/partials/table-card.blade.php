@php
    $statusKey = $table->status instanceof \App\Enums\TableStatus ? $table->status->value : (string) $table->status;
    $statusLabels = [
        'available' => 'Trống',
        'occupied' => 'Đang dùng',
        'reserved' => 'Đã đặt',
        'cleaning' => 'Cần dọn',
    ];
    $statusStyles = [
        'available' => 'border-emerald-300 bg-emerald-100 text-emerald-900',
        'occupied' => 'border-slate-300 bg-slate-200 text-slate-900',
        'reserved' => 'border-sky-300 bg-sky-100 text-sky-900',
        'cleaning' => 'border-amber-300 bg-amber-100 text-amber-900',
    ];
    $statusDots = [
        'available' => 'bg-emerald-500',
        'occupied' => 'bg-slate-500',
        'reserved' => 'bg-sky-500',
        'cleaning' => 'bg-amber-500',
    ];
@endphp

<div
    data-table-card
    data-table-id="{{ $table->id }}"
    data-code="{{ $table->code }}"
    data-area-id="{{ $table->area_id }}"
    data-area-name="{{ $table->area?->name ?? 'Khu vực chung' }}"
    data-seats="{{ $table->seats }}"
    data-status="{{ $statusKey }}"
    data-session-id="{{ $openSession?->id ?? '' }}"
    data-session-guest-count="{{ $openSession?->guest_count ?? '' }}"
    data-session-employee="{{ $openSession?->openedByUser?->full_name ?? '' }}"
    data-table-transfer-history="{{ json_encode($transferHistory) }}"
    class="group flex min-h-40 flex-col rounded-2xl border p-4 text-left shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md {{ $selected ? 'border-emerald-600 bg-emerald-50 ring-2 ring-emerald-200' : 'border-slate-300 bg-white hover:border-slate-400' }}"
>
    <button
        type="button"
        data-card-select
        aria-pressed="{{ $selected ? 'true' : 'false' }}"
        class="flex flex-1 flex-col justify-between rounded-xl text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600"
    >
        <span class="flex items-start justify-between gap-3">
            <span data-card-icon class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-200 text-slate-800 transition group-hover:bg-emerald-100 group-hover:text-emerald-900">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5V6.75A2.25 2.25 0 0 1 6.75 4.5h10.5a2.25 2.25 0 0 1 2.25 2.25v3.75M3.75 19.5v-6A2.25 2.25 0 0 1 6 11.25h12a2.25 2.25 0 0 1 2.25 2.25v6M3.75 15h16.5M6.75 19.5v-2.25m10.5 2.25v-2.25" />
                </svg>
            </span>
            <span data-card-status class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $statusStyles[$statusKey] ?? 'border-slate-200 bg-slate-50 text-slate-700' }}">
                <span data-card-status-dot class="h-1.5 w-1.5 rounded-full {{ $statusDots[$statusKey] ?? 'bg-slate-500' }}"></span>
                <span data-card-status-label>{{ $statusLabels[$statusKey] ?? 'Không rõ' }}</span>
            </span>
        </span>

        <span class="mt-5 block">
            <span class="block text-xl font-semibold tracking-tight text-slate-900">{{ $table->code }}</span>
            <span class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 18.75a4 4 0 0 0-8 0m8 0h3.25v-.5a3.25 3.25 0 0 0-5.49-2.35M8 18.75H4.75v-.5a3.25 3.25 0 0 1 5.49-2.35M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm6.25 1.25a2.25 2.25 0 1 0 0-4.5" />
                </svg>
                {{ $table->seats }} chỗ ngồi
            </span>
        </span>
    </button>
    <p data-card-session class="{{ $openSession ? '' : 'hidden ' }}mt-3 rounded-lg bg-emerald-50 px-2.5 py-2 text-xs font-medium text-emerald-900">
        Phiên #<span data-card-session-id>{{ $openSession?->id }}</span> · <span data-card-session-guest-count>{{ $openSession?->guest_count }}</span> khách
    </p>
</div>
