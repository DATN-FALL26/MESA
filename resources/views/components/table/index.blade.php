<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'w-full text-left border-collapse text-sm text-slate-600']) }}>
        @if(isset($head))
            <thead class="bg-slate-50/80 text-slate-700 border-b border-slate-200">
                <tr>
                    {{ $head }}
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-slate-100 bg-white">
            {{ $slot }}
        </tbody>
        @if(isset($foot))
            <tfoot class="bg-slate-50 border-t border-slate-200 text-slate-900 font-semibold">
                {{ $foot }}
            </tfoot>
        @endif
    </table>
</div>
