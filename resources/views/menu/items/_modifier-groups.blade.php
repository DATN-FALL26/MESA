{{-- Gán nhóm topping cho món. Thứ tự các dòng được tick chính là thứ tự hiển thị cho khách. --}}
<x-menu.section-card id="groups" title="Nhóm topping" description="Tick các nhóm khách được chọn khi gọi món này. Dùng mũi tên để sắp xếp thứ tự hiển thị.">
    <x-slot:actions>
        <a href="{{ route('menu.modifier-groups.create') }}" class="text-sm font-medium text-indigo-700 hover:underline">Tạo nhóm mới</a>
    </x-slot:actions>

    @php
        $ordered = collect($assignedIds)->map(fn ($id) => $groups->firstWhere('id', $id))->filter()
            ->concat($groups->whereNotIn('id', $assignedIds));
    @endphp

    @if ($ordered->isEmpty())
        <p class="text-sm text-slate-500">Chưa có nhóm topping nào. Tạo nhóm trước rồi quay lại gán cho món.</p>
    @else
        <form method="POST" action="{{ route('menu.items.modifier-groups.sync', $menuItem) }}">
            @csrf
            @method('PUT')
            <ul id="group-list" class="space-y-2">
                @foreach ($ordered as $group)
                    <li class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2.5">
                        <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3">
                            <input type="checkbox" name="group_ids[]" value="{{ $group->id }}" class="size-4 accent-indigo-600" @checked(in_array($group->id, $assignedIds, true))>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium text-slate-900">{{ $group->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $group->modifiers_count }} topping · khách chọn {{ $group->min_select }} đến {{ $group->max_select }}</span>
                            </span>
                            @unless ($group->is_active)
                                <x-menu.badge tone="neutral">Đang tắt</x-menu.badge>
                            @endunless
                        </label>
                        <div class="flex shrink-0 items-center gap-1">
                            <button type="button" data-move="-1" class="inline-flex size-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900" aria-label="Chuyển {{ $group->name }} lên trên">
                                <x-menu.icon name="arrow-up" class="size-4" />
                            </button>
                            <button type="button" data-move="1" class="inline-flex size-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900" aria-label="Chuyển {{ $group->name }} xuống dưới">
                                <x-menu.icon name="arrow-down" class="size-4" />
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4 flex justify-end">
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Lưu nhóm topping</button>
            </div>
        </form>

        <script>
            (function () {
                var list = document.getElementById('group-list');
                if (!list) return;
                list.addEventListener('click', function (event) {
                    var button = event.target.closest('[data-move]');
                    if (!button) return;
                    var row = button.closest('li');
                    var direction = parseInt(button.dataset.move, 10);
                    if (direction < 0 && row.previousElementSibling) {
                        list.insertBefore(row, row.previousElementSibling);
                    } else if (direction > 0 && row.nextElementSibling) {
                        list.insertBefore(row.nextElementSibling, row);
                    }
                });
            })();
        </script>
    @endif
</x-menu.section-card>
