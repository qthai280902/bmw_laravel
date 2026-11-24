<x-admin-layout>
    <x-admin.page-header
        :back-url="route('admin.users.index')"
        back-label="Danh sách nhân sự"
        eyebrow="Access control · Thêm nhân sự"
        title="Khởi tạo"
        accent="Hồ sơ Nhân sự"
        description="Tạo tài khoản nhân sự nội bộ với đầy đủ thông tin chức danh, phòng ban, số điện thoại và phân quyền Showroom."
    />

    <div class="max-w-3xl">
        <x-admin.card class="!p-6">
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="_from_create_page" value="1">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-admin.form-field name="name" label="Họ và tên *">
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            placeholder="Nguyễn Văn A"
                            class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                        >
                    </x-admin.form-field>

                    <x-admin.form-field name="email" label="Email công vụ (@bmw.com) *">
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            placeholder="sales01@bmw.com"
                            class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                        >
                    </x-admin.form-field>

                    <x-admin.form-field name="phone" label="Số điện thoại nội bộ">
                        <input
                            type="text"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="0901234567"
                            class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium tabular-nums text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                        >
                    </x-admin.form-field>

                    <x-admin.form-field name="role" label="Vai trò hệ thống *">
                        <x-ui.custom-select
                            name="role"
                            :value="(string) old('role', 'sales')"
                            :options="$roles"
                        />
                    </x-admin.form-field>

                    <x-admin.form-field name="job_title" label="Chức danh công việc">
                        <input
                            type="text"
                            name="job_title"
                            value="{{ old('job_title') }}"
                            placeholder="Chuyên viên Tư vấn Bán hàng Senior"
                            class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                        >
                    </x-admin.form-field>

                    <x-admin.form-field name="department" label="Phòng ban / Khu vực">
                        <input
                            type="text"
                            name="department"
                            value="{{ old('department') }}"
                            placeholder="Khối Kinh doanh Ô tô BMW"
                            class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                        >
                    </x-admin.form-field>
                </div>

                <x-admin.form-field name="password" label="Mật khẩu khởi tạo (tối thiểu 8 ký tự) *">
                    <input
                        type="password"
                        name="password"
                        required
                        minlength="8"
                        placeholder="Nhập mật khẩu ban đầu..."
                        class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                    >
                </x-admin.form-field>

                <x-admin.form-field name="notes" label="Ghi chú nhân sự">
                    <textarea
                        name="notes"
                        rows="3"
                        placeholder="Ghi chú phân công chuyên trách dòng xe, ca trực..."
                        class="w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 py-2.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                    >{{ old('notes') }}</textarea>
                </x-admin.form-field>

                <div class="flex justify-end border-t border-white/[0.08] pt-4">
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center rounded-xl bg-[#1C69D4] px-6 text-xs font-bold text-white shadow-sm shadow-[#1C69D4]/25 transition-colors hover:bg-[#2575e6] cursor-pointer"
                    >
                        Lưu &amp; Mở hồ sơ nhân sự
                    </button>
                </div>
            </form>
        </x-admin.card>
    </div>
</x-admin-layout>
