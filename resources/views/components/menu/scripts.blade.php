{{-- JavaScript dùng chung của module Thực đơn: mở/đóng modal, điền sẵn form khi sửa, hộp xác nhận. --}}
<script>
    window.MenuUI = (function () {
        function byId(id) { return document.getElementById(id); }

        function openModal(id) {
            var modal = byId(id);
            if (!modal) return null;
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            modal.dispatchEvent(new CustomEvent('menu:opened', { bubbles: true }));
            var focusable = modal.querySelector('input:not([type=hidden]):not([type=checkbox]):not([type=radio]), select, textarea');
            if (focusable) setTimeout(function () { focusable.focus(); }, 30);
            return modal;
        }

        function closeModal(modal) {
            modal.classList.add('hidden');
            if (!document.querySelector('[data-menu-modal]:not(.hidden)')) {
                document.body.classList.remove('overflow-hidden');
            }
        }

        function setField(form, name, value) {
            form.querySelectorAll('[name="' + name + '"]').forEach(function (field) {
                if (field.type === 'hidden') {
                    // Ô ẩn đi kèm checkbox (giá trị 0) giữ nguyên; ô ẩn thường (vd. id) nhận giá trị.
                    if (form.querySelector('[name="' + name + '"][type=checkbox]')) return;
                    field.value = value === null || value === undefined ? '' : value;
                } else if (field.type === 'checkbox') {
                    field.checked = value === true || value === 1 || value === '1';
                } else if (field.type === 'radio') {
                    field.checked = String(field.value) === String(value);
                } else {
                    field.value = value === null || value === undefined ? '' : value;
                }
            });
        }

        function clearForm(form) {
            form.querySelectorAll('input, select, textarea').forEach(function (field) {
                if (field.type === 'hidden' || field.type === 'submit' || field.name.charAt(0) === '_') return;
                if (field.type === 'checkbox' || field.type === 'radio') field.checked = false;
                else if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0;
                    field.querySelectorAll('option').forEach(function (option) { option.disabled = false; });
                } else field.value = '';
            });
        }

        function applyConfig(modal, form, config) {
            var action = config.action || form.dataset.defaultAction;
            form.action = action;
            form.querySelector('[name="_method"]').value = config.method || 'POST';
            form.querySelector('[name="_action"]').value = action;
            form.querySelector('[name="_title"]').value = config.title || '';
            var title = modal.querySelector('[data-modal-title]');
            if (title && config.title) title.textContent = config.title;
            var submit = form.querySelector('[data-submit-label]');
            if (submit && config.submit) submit.textContent = config.submit;
        }

        // Mở modal: config = { action, method, title, submit, values: { tên_trường: giá_trị } }
        function open(id, config) {
            config = config || {};
            var modal = byId(id);
            var form = modal ? modal.querySelector('form[data-menu-form]') : null;
            if (modal && form) {
                clearForm(form);
                Object.keys(config.values || {}).forEach(function (name) { setField(form, name, config.values[name]); });
                // disable: { tên_select: [giá_trị_không_được_chọn] }, ví dụ không cho chọn chính danh mục làm cha của nó.
                Object.keys(config.disable || {}).forEach(function (name) {
                    var select = form.querySelector('select[name="' + name + '"]');
                    if (!select) return;
                    select.querySelectorAll('option').forEach(function (option) {
                        option.disabled = config.disable[name].map(String).indexOf(option.value) !== -1;
                    });
                });
                applyConfig(modal, form, config);
                form.querySelectorAll('[data-field-error]').forEach(function (node) { node.classList.add('hidden'); });
            }
            openModal(id);
            if (typeof config.afterOpen === 'function') config.afterOpen(form);
        }

        // Mở lại modal sau khi validate lỗi: giữ nguyên các giá trị người dùng đã nhập (do old() điền sẵn).
        function reopen(id, config) {
            var modal = byId(id);
            var form = modal ? modal.querySelector('form[data-menu-form]') : null;
            if (modal && form) applyConfig(modal, form, config || {});
            openModal(id);
        }

        function confirmDialog(config) {
            var modal = byId('menu-confirm-modal');
            if (!modal) return;
            modal.querySelector('[data-confirm-title]').textContent = config.title || 'Xác nhận';
            modal.querySelector('[data-confirm-subtitle]').textContent = config.subtitle || '';
            modal.querySelector('[data-confirm-message]').textContent = config.message || '';
            var form = modal.querySelector('[data-confirm-form]');
            form.action = config.action;
            form.querySelector('[data-confirm-method]').value = config.method || 'DELETE';
            var submit = modal.querySelector('[data-confirm-submit]');
            submit.textContent = config.confirm || 'Xác nhận';
            var tone = config.tone === 'primary'
                ? ['bg-emerald-600', 'hover:bg-emerald-700']
                : ['bg-rose-600', 'hover:bg-rose-700'];
            submit.classList.remove('bg-rose-600', 'hover:bg-rose-700', 'bg-emerald-600', 'hover:bg-emerald-700');
            submit.classList.add(tone[0], tone[1]);
            var icon = modal.querySelector('[data-confirm-icon]');
            icon.classList.remove('bg-rose-100', 'text-rose-600', 'bg-emerald-100', 'text-emerald-600');
            icon.classList.add.apply(icon.classList, config.tone === 'primary' ? ['bg-emerald-100', 'text-emerald-600'] : ['bg-rose-100', 'text-rose-600']);
            openModal('menu-confirm-modal');
        }

        function parse(node, attribute) {
            try { return JSON.parse(node.getAttribute(attribute) || '{}'); } catch (error) { return {}; }
        }

        document.addEventListener('click', function (event) {
            var opener = event.target.closest('[data-modal-open]');
            if (opener) {
                event.preventDefault();
                open(opener.getAttribute('data-modal-open'), parse(opener, 'data-config'));
                return;
            }
            var confirmer = event.target.closest('[data-confirm]');
            if (confirmer) {
                event.preventDefault();
                confirmDialog(parse(confirmer, 'data-confirm'));
                return;
            }
            var closer = event.target.closest('[data-modal-close]');
            if (closer) {
                var host = closer.closest('[data-menu-modal]');
                if (host) closeModal(host);
                return;
            }
            // Bấm vào nền mờ để đóng.
            if (event.target.hasAttribute && event.target.hasAttribute('data-menu-modal')) closeModal(event.target);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('[data-menu-modal]:not(.hidden)').forEach(closeModal);
            }
        });

        return { open: open, reopen: reopen, confirm: confirmDialog, close: function (id) { var m = byId(id); if (m) closeModal(m); } };
    })();
</script>
