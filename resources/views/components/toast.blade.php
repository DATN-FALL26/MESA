{{-- Component Toast Notification hiển thị thông báo phản hồi thao tác --}}
<div 
    x-data="{
        notifications: [],
        add(message, type = 'success', duration = 4000) {
            const id = Date.now() + Math.random();
            this.notifications.push({ id, message, type });
            setTimeout(() => this.remove(id), duration);
        },
        remove(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        }
    }"
    x-init="
        window.mesaToast = (msg, type = 'success') => add(msg, type);
        @if(session('success'))
            add(@js(session('success')), 'success');
        @endif
        @if(session('error'))
            add(@js(session('error')), 'error', 6000);
        @endif
        @if(session('warning'))
            add(@js(session('warning')), 'warning', 5000);
        @endif
        @if(session('info'))
            add(@js(session('info')), 'info');
        @endif
    "
    class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"
    style="display: none;"
    x-show="notifications.length > 0"
>
    <template x-for="item in notifications" :key="item.id">
        <div 
            class="pointer-events-auto flex items-start gap-3 p-3.5 rounded-lg border shadow-sm transition-all duration-200"
            :class="{
                'bg-white border-emerald-200 text-slate-800': item.type === 'success',
                'bg-white border-rose-200 text-slate-800': item.type === 'error',
                'bg-white border-amber-200 text-slate-800': item.type === 'warning',
                'bg-white border-slate-200 text-slate-800': item.type === 'info'
            }"
        >
            <!-- Icon -->
            <div class="shrink-0 mt-0.5">
                <template x-if="item.type === 'success'">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-600">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </span>
                </template>
                <template x-if="item.type === 'error'">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-rose-100 text-rose-600">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </span>
                </template>
                <template x-if="item.type === 'warning'">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-600">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 6.75h.008v.008H12v-.008Z" />
                        </svg>
                    </span>
                </template>
                <template x-if="item.type === 'info'">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-slate-100 text-slate-600">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                    </span>
                </template>
            </div>

            <!-- Content -->
            <div class="flex-1 text-xs">
                <p class="font-medium text-slate-900" x-text="item.type === 'success' ? 'Thành công' : (item.type === 'error' ? 'Có lỗi xảy ra' : (item.type === 'warning' ? 'Cảnh báo' : 'Thông báo'))"></p>
                <p class="text-slate-600 mt-0.5" x-text="item.message"></p>
            </div>

            <!-- Close Button -->
            <button 
                type="button" 
                @click="remove(item.id)" 
                class="shrink-0 text-slate-400 hover:text-slate-600 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>

{{-- Fallback khi không có AlpineJS: Vanilla JS đơn giản --}}
<div id="vanilla-toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"></div>

<script>
window.showToast = function(message, type = 'success') {
    if (window.mesaToast) {
        window.mesaToast(message, type);
        return;
    }
    const container = document.getElementById('vanilla-toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    const borderColor = type === 'success' ? 'border-emerald-200' : (type === 'error' ? 'border-rose-200' : 'border-slate-200');
    const badgeColor = type === 'success' ? 'bg-emerald-100 text-emerald-600' : (type === 'error' ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-600');
    const title = type === 'success' ? 'Thành công' : (type === 'error' ? 'Có lỗi xảy ra' : 'Thông báo');

    toast.className = `pointer-events-auto flex items-start gap-3 p-3.5 rounded-lg border bg-white shadow-sm transition-all duration-200 ${borderColor}`;
    toast.innerHTML = `
        <span class="shrink-0 mt-0.5 inline-flex items-center justify-center w-5 h-5 rounded-full ${badgeColor}">•</span>
        <div class="flex-1 text-xs">
            <p class="font-medium text-slate-900">${title}</p>
            <p class="text-slate-600 mt-0.5">${message}</p>
        </div>
    `;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
};

@if(session('success'))
    document.addEventListener('DOMContentLoaded', () => window.showToast(@js(session('success')), 'success'));
@endif
@if(session('error'))
    document.addEventListener('DOMContentLoaded', () => window.showToast(@js(session('error')), 'error'));
@endif
@if(session('warning'))
    document.addEventListener('DOMContentLoaded', () => window.showToast(@js(session('warning')), 'warning'));
@endif
</script>
