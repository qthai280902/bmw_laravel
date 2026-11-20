<div id="admin-delete-confirm-modal" class="fixed inset-0 z-[120] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-black/80" data-admin-delete-cancel></div>

    <div class="relative flex min-h-screen items-center justify-center px-4 py-8">
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-white/[0.08] bg-[#0D121C] shadow-2xl shadow-black/70">
            <div class="border-b border-white/[0.08] px-6 py-5">
                <p class="text-xs font-semibold text-rose-400">Xác nhận xóa</p>
                <h2 class="mt-1.5 text-lg font-bold text-white">Thao tác không thể hoàn tác</h2>
            </div>

            <div class="px-6 py-6">
                <p class="text-sm leading-6 text-zinc-400" data-admin-delete-message>
                    Bạn có chắc chắn muốn xóa bản ghi này?
                </p>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-white/[0.08] bg-[#070A10]/60 px-6 py-4">
                <button type="button" class="h-10 rounded-xl border border-white/10 bg-[#131A29] px-5 text-xs font-semibold text-zinc-300 transition-colors hover:text-white cursor-pointer" data-admin-delete-cancel>
                    Hủy
                </button>
                <button type="button" class="h-10 rounded-xl bg-rose-600 px-5 text-xs font-semibold text-white transition-colors hover:bg-rose-500 cursor-pointer" data-admin-delete-confirm>
                    Xóa
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('admin-delete-confirm-modal');

        if (!modal || modal.dataset.initialized === 'true') {
            return;
        }

        modal.dataset.initialized = 'true';

        const message = modal.querySelector('[data-admin-delete-message]');
        const confirmButton = modal.querySelector('[data-admin-delete-confirm]');
        const cancelButtons = modal.querySelectorAll('[data-admin-delete-cancel]');
        let pendingForm = null;

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            pendingForm = null;
        };

        const openModal = (form) => {
            pendingForm = form;

            if (message) {
                message.textContent = form.dataset.confirmMessage || 'Bạn có chắc chắn muốn xóa bản ghi này?';
            }

            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            confirmButton?.focus();
        };

        document.addEventListener('submit', (event) => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement) || !form.classList.contains('admin-delete-form')) {
                return;
            }

            if (form.dataset.confirmed === 'true') {
                return;
            }

            event.preventDefault();
            openModal(form);
        }, true);

        confirmButton?.addEventListener('click', () => {
            if (!pendingForm) {
                return;
            }

            const form = pendingForm;
            form.dataset.confirmed = 'true';
            closeModal();
            HTMLFormElement.prototype.submit.call(form);
        });

        cancelButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    });
</script>
