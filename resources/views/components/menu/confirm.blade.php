@props(['action', 'title', 'message' => null, 'confirm' => 'Xác nhận', 'method' => 'DELETE', 'tone' => 'danger'])
@php
    $confirmClass = $tone === 'danger'
        ? 'bg-rose-600 hover:bg-rose-500 focus-visible:outline-rose-600'
        : 'bg-indigo-600 hover:bg-indigo-500 focus-visible:outline-indigo-600';
    $iconWrap = $tone === 'danger' ? 'bg-rose-50 text-rose-600' : 'bg-indigo-50 text-indigo-600';
@endphp
<span class="inline-flex">
    <button type="button" onclick="this.nextElementSibling.showModal()" {{ $attributes }}>{{ $slot }}</button>
    <dialog class="m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl border border-slate-200 bg-white p-0 text-slate-800 shadow-xl backdrop:bg-slate-900/50"
        onclick="if (event.target === this) this.close()">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif
            <div class="flex gap-4 p-6">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $iconWrap }}">
                    <x-menu.icon :name="$tone === 'danger' ? 'warning' : 'info'" class="size-5" />
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
                    @if ($message)
                        <p class="mt-1.5 text-sm text-slate-600">{{ $message }}</p>
                    @endif
                </div>
            </div>
            <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-100 bg-slate-50 px-6 py-3">
                <button type="button" onclick="this.closest('dialog').close()" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Hủy</button>
                <button type="submit" class="rounded-lg px-3.5 py-2 text-sm font-semibold text-white focus-visible:outline-2 {{ $confirmClass }}">{{ $confirm }}</button>
            </div>
        </form>
    </dialog>
</span>
