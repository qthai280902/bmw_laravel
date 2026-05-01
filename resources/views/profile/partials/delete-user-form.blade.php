<section class="space-y-6">
    <div class="space-y-4">
        <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest leading-relaxed">
            Hành động này sẽ xóa vĩnh viễn tài khoản của bạn. Mọi dữ liệu đặt lịch, lịch sử cọc xe và cấu hình cá nhân sẽ không thể khôi phục.
        </p>
    </div>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Bắt đầu xóa tài khoản') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-12 bg-zinc-950 border-2 border-rose-900/50 shadow-[0_0_100px_rgba(225,29,72,0.1)]">
            @csrf
            @method('delete')

            <h2 class="text-3xl font-black uppercase tracking-tighter text-white mb-4">
                Xác nhận <span class="text-rose-600">xóa</span>
            </h2>

            <p class="text-[10px] text-zinc-500 font-black uppercase tracking-[0.2em]  mb-8">
                Vui lòng nhập mật khẩu xác nhận bạn muốn xóa định danh khỏi hệ thống BMW Showroom.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Mật khẩu hiện tại') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full"
                    placeholder="{{ __('Mật khẩu') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-10 flex justify-end gap-4">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Hủy bỏ') }}
                </x-secondary-button>

                <x-danger-button>
                    {{ __('Xác nhận xóa tài khoản') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
