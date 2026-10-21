{{-- Banner: bàn vừa xong món --}}
@if ($justReady)
    <div class="mb-4 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
        <span aria-hidden="true">✓</span>
        {{ $justReady['place'] }} vừa xong món, mang ra bàn
    </div>
@endif

<div class="grid gap-4 lg:grid-cols-3">
    {{-- Món chờ phục vụ --}}
    <section class="lg:col-span-2" aria-label="Món chờ phục vụ">
        <header class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-bold text-stone-800">Món chờ phục vụ</h2>
            <span class="flex h-6 min-w-6 items-center justify-center rounded-full bg-emerald-800 px-2 text-xs font-bold text-white">{{ $readyGroups->count() }}</span>
        </header>

        <div class="flex flex-col gap-3">
            @forelse ($readyGroups as $group)
                @php
                    $isJustReady = $justReady && $justReady['order']->is($group['order']);
                    $minutesAgo = (int) $group['readyAt']->diffInMinutes(now());
                @endphp
                <article data-ready-order-id="{{ $group['order']->id }}"
                         class="flex items-center justify-between gap-4 rounded-xl bg-white p-4 shadow-sm {{ $isJustReady ? 'ring-2 ring-emerald-700' : 'ring-1 ring-stone-200' }}">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-lg font-bold text-stone-900">{{ $group['place'] }}</p>
                            <span data-ready-at="{{ $group['readyAt']->toIso8601String() }}"
                                  class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $isJustReady ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-100 text-stone-600' }}">
                                {{ $minutesAgo < 1 ? 'Vừa xong' : 'Xong '.$minutesAgo.' phút trước' }}
                            </span>
                        </div>
                        <ul class="mt-1.5 space-y-0.5 text-sm text-stone-800">
                            @foreach ($group['items'] as $orderItem)
                                <li>
                                    <span class="font-bold">{{ $orderItem->quantity }}x</span>
                                    {{ $orderItem->item_name_snapshot }}
                                    @if ($orderItem->variant_name_snapshot)
                                        <span class="text-stone-500">({{ $orderItem->variant_name_snapshot }})</span>
                                    @endif
                                    @if ($orderItem->modifiers->isNotEmpty())
                                        <span class="text-xs text-stone-500">+ {{ $orderItem->modifiers->pluck('name_snapshot')->join(', ') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('kitchen.serve.served', $group['order']) }}" data-serve-action class="shrink-0">
                        @csrf
                        <button type="submit" class="rounded-lg bg-emerald-800 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-700">
                            Đã phục vụ
                        </button>
                    </form>
                </article>
            @empty
                <p class="rounded-xl border-2 border-dashed border-stone-300 bg-white/50 px-4 py-12 text-center text-sm text-stone-500">
                    Chưa có món nào chờ mang ra. Món bếp vừa xong sẽ tự hiện ở đây.
                </p>
            @endforelse
        </div>
    </section>

    {{-- Đang ở bếp --}}
    <aside class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-stone-200" aria-label="Đang ở bếp">
        <h2 class="mb-3 text-sm font-bold text-stone-800">Đang ở bếp</h2>

        <ul class="divide-y divide-stone-100">
            @forelse ($kitchenGroups as $group)
                @php
                    $elapsedSeconds = (int) $group['firedAt']->diffInSeconds(now());
                    $isOverdue = $elapsedSeconds >= $overdueMinutes * 60;
                @endphp
                <li class="flex items-center justify-between py-2.5 text-sm">
                    <span class="font-medium text-stone-800">{{ $group['place'] }}</span>
                    @if ($group['isCooking'])
                        <span data-kitchen-fired-at="{{ $group['firedAt']->toIso8601String() }}" data-cooking="1"
                              class="rounded-full px-2.5 py-0.5 font-mono text-xs font-semibold tabular-nums {{ $isOverdue ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                            Đang nấu {{ sprintf('%02d:%02d', intdiv($elapsedSeconds, 60), $elapsedSeconds % 60) }}
                        </span>
                    @else
                        <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-semibold text-stone-600">Mới</span>
                    @endif
                </li>
            @empty
                <li class="py-6 text-center text-sm text-stone-500">Bếp đang trống.</li>
            @endforelse
        </ul>
    </aside>
</div>
