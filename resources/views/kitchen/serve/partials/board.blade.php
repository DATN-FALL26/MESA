{{-- Banner: bàn vừa xong món --}}
@if ($justReady)
    <x-alert type="success" class="mb-5">
        {{ $justReady['place'] }} vừa xong món, mang ra bàn
    </x-alert>
@endif

<div class="grid gap-5 lg:grid-cols-3">
    {{-- Món chờ phục vụ --}}
    <x-card :padding="false" class="lg:col-span-2" aria-label="Món chờ phục vụ">
        <x-card.header title="Món chờ phục vụ" subtitle="Bàn chờ lâu nhất ở trên cùng">
            <x-slot:actions>
                <x-badge color="success">{{ $readyGroups->count() }} bàn</x-badge>
            </x-slot:actions>
        </x-card.header>

        <div class="flex flex-col gap-3 p-4">
            @forelse ($readyGroups as $group)
                @php
                    $isJustReady = $justReady && $justReady['order']->is($group['order']);
                    $minutesAgo = (int) $group['readyAt']->diffInMinutes(now());
                @endphp
                <article data-ready-order-id="{{ $group['order']->id }}"
                         class="flex items-center justify-between gap-4 rounded-xl border p-4 {{ $isJustReady ? 'border-emerald-300 bg-emerald-50/40' : 'border-slate-200 bg-white' }}">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-lg font-bold text-slate-900">{{ $group['place'] }}</p>
                            <x-badge :color="$isJustReady ? 'success' : 'neutral'" data-ready-at="{{ $group['readyAt']->toIso8601String() }}">
                                {{ $minutesAgo < 1 ? 'Vừa xong' : 'Xong '.$minutesAgo.' phút trước' }}
                            </x-badge>
                        </div>
                        <ul class="mt-1.5 space-y-0.5 text-sm text-slate-700">
                            @foreach ($group['items'] as $orderItem)
                                <li>
                                    <span class="font-bold text-slate-900">{{ $orderItem->quantity }}x</span>
                                    {{ $orderItem->item_name_snapshot }}
                                    @if ($orderItem->variant_name_snapshot)
                                        <span class="text-slate-500">({{ $orderItem->variant_name_snapshot }})</span>
                                    @endif
                                    @if ($orderItem->modifiers->isNotEmpty())
                                        <span class="text-xs text-slate-500">+ {{ $orderItem->modifiers->pluck('name_snapshot')->join(', ') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('kitchen.serve.served', $group['order']) }}" data-serve-action class="shrink-0">
                        @csrf
                        <x-button type="submit" variant="primary" size="lg">Đã phục vụ</x-button>
                    </form>
                </article>
            @empty
                <p class="rounded-xl border-2 border-dashed border-slate-200 px-4 py-12 text-center text-sm text-slate-500">
                    Chưa có món nào chờ mang ra. Món bếp vừa xong sẽ tự hiện ở đây.
                </p>
            @endforelse
        </div>
    </x-card>

    {{-- Đang ở bếp --}}
    <x-card :padding="false" aria-label="Đang ở bếp">
        <x-card.header title="Đang ở bếp" subtitle="Các bàn còn món chưa xong" />

        <ul class="divide-y divide-slate-100 px-6">
            @forelse ($kitchenGroups as $group)
                @php
                    $elapsedSeconds = (int) $group['firedAt']->diffInSeconds(now());
                    $isOverdue = $elapsedSeconds >= $overdueMinutes * 60;
                @endphp
                <li class="flex items-center justify-between py-3 text-sm">
                    <span class="font-medium text-slate-800">{{ $group['place'] }}</span>
                    @if ($group['isCooking'])
                        <x-badge :color="$isOverdue ? 'danger' : 'warning'" class="font-mono tabular-nums"
                                 data-kitchen-fired-at="{{ $group['firedAt']->toIso8601String() }}" data-cooking="1">
                            Đang nấu {{ sprintf('%02d:%02d', intdiv($elapsedSeconds, 60), $elapsedSeconds % 60) }}
                        </x-badge>
                    @else
                        <x-badge color="neutral">Mới</x-badge>
                    @endif
                </li>
            @empty
                <li class="py-8 text-center text-sm text-slate-500">Bếp đang trống.</li>
            @endforelse
        </ul>
    </x-card>
</div>
