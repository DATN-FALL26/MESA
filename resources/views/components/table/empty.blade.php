@props([
    'colspan' => 10,
    'message' => 'Không tìm thấy dữ liệu phù hợp',
    'description' => null,
])

<tr>
    <td colspan="{{ $colspan }}" class="px-6 py-12 text-center">
        <div class="flex flex-col items-center justify-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3.75h3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-slate-800">{{ $message }}</p>
            @if($description)
                <p class="text-xs text-slate-500 mt-1 max-w-sm">{{ $description }}</p>
            @endif
            @if(isset($action))
                <div class="mt-4">
                    {{ $action }}
                </div>
            @endif
        </div>
    </td>
</tr>
