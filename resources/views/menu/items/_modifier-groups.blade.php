{{-- Gán nhóm topping cho món. Thứ tự các nhóm được tick chính là thứ tự hiển thị cho khách. --}}
@php
    $ordered = collect($assignedIds)->map(fn ($id) => $groups->firstWhere('id', $id))->filter()
        ->concat($groups->whereNotIn('id', $assignedIds));
@endphp

<div id="groups" class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs scroll-mt-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Nhóm topping áp dụng cho món</h2>
            <p class="text-[10px] text-slate-400 mt-0.5">Tick các nhóm khách được chọn khi gọi món này. Dùng nút mũi tên để sắp thứ tự hiển thị.</p>
        </div>
        <x-menu.button :href="route('menu.modifier-groups.index')" variant="outline">Quản lý nhóm topping</x-menu.button>
    </div>

    @if ($ordered->isEmpty())
        <p class="px-6 py-8 text-center text-xs text-slate-400">Chưa có nhóm topping nào. Hãy tạo nhóm trước rồi quay lại gán cho món.</p>
    @else
        <form method="POST" action="{{ route('menu.items.modifier-groups.sync', $menuItem) }}" class="text-xs">
            @csrf
            @method('PUT')
            <ul id="group-list" class="p-6 space-y-2">
                @foreach ($ordered as $group)
                    <li class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5">
                        <label class="flex flex-1 min-w-0 items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="group_ids[]" value="{{ $group->id }}" class="rounded border-slate-300 accent-emerald-600" @checked(in_array($group->id, $assignedIds, true))>
                            <span class="min-w-0">
                                <span class="block font-semibold text-slate-900 truncate">{{ $group->name }}</span>
                                <span class="block text-[10px] text-slate-400">{{ $group->modifiers_count }} topping · khách chọn {{ $group->min_select }} đến {{ $group->max_select }}</span>
                            </span>
                            @unless ($group->is_active)
                                <x-menu.badge>Đang tắt</x-menu.badge>
                            @endunless
                        </label>
                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" data-move="-1" aria-label="Chuyển {{ $group->name }} lên trên"
                                class="w-7 h-7 flex items-center justify-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" /></svg>
                            </button>
                            <button type="button" data-move="1" aria-label="Chuyển {{ $group->name }} xuống dưới"
                                class="w-7 h-7 flex items-center justify-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="flex items-center justify-end px-6 py-4 bg-slate-50 border-t border-slate-200">
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors">Lưu nhóm topping</button>
            </div>
        </form>

        <script>
            (function () {
                var list = document.getElementById('group-list');
                list.addEventListener('click', function (event) {
                    var button = event.target.closest('[data-move]');
                    if (!button) return;
                    var row = button.closest('li');
                    if (parseInt(button.dataset.move, 10) < 0 && row.previousElementSibling) {
                        list.insertBefore(row, row.previousElementSibling);
                    } else if (parseInt(button.dataset.move, 10) > 0 && row.nextElementSibling) {
                        list.insertBefore(row.nextElementSibling, row);
                    }
                });
            })();
        </script>
    @endif
</div>
