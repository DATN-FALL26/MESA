<!-- TOAST NOTIFICATION CONTAINER (TAILWIND NATIVE) -->
<div
    id="mesa-toast-container"
    class="fixed top-5 right-5 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none"
    aria-live="polite"
></div>

<script>
    (function () {
        const container = document.getElementById('mesa-toast-container');

        const icons = {
            success: `
                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>`,
            error: `
                <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>`,
            warning: `
                <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>`,
            info: `
                <div class="w-8 h-8 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                </div>`
        };

        const titles = {
            success: 'Thành công',
            error: 'Đã có lỗi xảy ra',
            warning: 'Cảnh báo',
            info: 'Thông tin'
        };

        const borderColors = {
            success: 'border-emerald-200',
            error: 'border-rose-200',
            warning: 'border-amber-200',
            info: 'border-sky-200'
        };

        window.toast = {
            show(type, message, customTitle = null, duration = 4000) {
                if (!container) return;

                const toast = document.createElement('div');
                toast.className = `pointer-events-auto flex items-start gap-3 p-4 bg-white border ${borderColors[type] || 'border-slate-200'} rounded-xl shadow-lg transition-all duration-300 transform translate-x-full opacity-0`;

                toast.innerHTML = `
                    ${icons[type] || icons.info}
                    <div class="flex-1 min-w-0 pt-0.5">
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">${customTitle || titles[type]}</h4>
                        <p class="text-sm text-slate-600 mt-0.5 leading-snug break-words">${message}</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600 p-1 -mr-1 -mt-1 rounded-lg transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                `;

                // Đóng toast khi bấm nút X
                const closeBtn = toast.querySelector('button');
                closeBtn.addEventListener('click', () => dismissToast(toast));

                container.appendChild(toast);

                // Hiệu ứng slide-in mượt mà
                requestAnimationFrame(() => {
                    toast.classList.remove('translate-x-full', 'opacity-0');
                    toast.classList.add('translate-x-0', 'opacity-100');
                });

                // Tự động đóng sau duration (ms)
                let timer = setTimeout(() => dismissToast(toast), duration);

                // Tạm dừng timer khi di chuột vào toast
                toast.addEventListener('mouseenter', () => clearTimeout(timer));
                toast.addEventListener('mouseleave', () => {
                    timer = setTimeout(() => dismissToast(toast), 1500);
                });
            },

            success(message, title = null) { this.show('success', message, title); },
            error(message, title = null) { this.show('error', message, title, 5000); },
            warning(message, title = null) { this.show('warning', message, title, 5000); },
            info(message, title = null) { this.show('info', message, title); }
        };

        function dismissToast(toast) {
            toast.classList.remove('translate-x-0', 'opacity-100');
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }

        // Tự động quét và hiển thị Flash message từ Session Laravel
        document.addEventListener('DOMContentLoaded', () => {
            @if(session('success'))
                window.toast.success(@json(session('success')));
            @endif

            @if(session('error'))
                window.toast.error(@json(session('error')));
            @endif

            @if(session('warning'))
                window.toast.warning(@json(session('warning')));
            @endif

            @if(session('info'))
                window.toast.info(@json(session('info')));
            @endif
        });
    })();
</script>
