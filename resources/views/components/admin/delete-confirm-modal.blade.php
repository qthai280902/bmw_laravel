<div id="admin-delete-confirm-modal" class="fixed inset-0 z-[120] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-black/80" data-admin-delete-cancel></div>

    <div class="relative flex min-h-screen items-center justify-center px-4 py-8">
        <div class="w-full max-w-md border border-zinc-800 bg-zinc-950 shadow-2xl">
            <div class="border-b border-zinc-800 px-6 py-5">
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-rose-500">Xác nhận xóa</p>
                <h2 class="mt-2 text-xl font-black uppercase tracking-tight text-white">Thao tác không thể hoàn tác</h2>
            </div>

            <div class="px-6 py-6">
                <p class="text-sm font-medium leading-6 text-zinc-400" data-admin-delete-message>
                    Bạn có chắc chắn muốn xóa bản ghi này?
                </p>
            </div>

            <div class="grid grid-cols-2 border-t border-zinc-800">
                <button type="button" class="border-r border-zinc-800 px-5 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 transition-all hover:bg-zinc-900 hover:text-white" data-admin-delete-cancel>
                    Hủy
                </button>
                <button type="button" class="bg-rose-600 px-5 py-4 text-[10px] font-black uppercase tracking-[0.2em] text-white transition-all hover:bg-rose-500" data-admin-delete-confirm>
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
