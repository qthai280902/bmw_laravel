<x-admin-layout>
    <x-admin.page-header
        :back-url="route('admin.users.show', $staffUser)"
        back-label="Quay lại hồ sơ nhân sự"
        :eyebrow="'Cập nhật nhân sự · #STF-' . str_pad((string) $staffUser->id, 4, '0', STR_PAD_LEFT)"
        title="Chỉnh sửa"
        :accent="$staffUser->name"
        description="Cập nhật thông tin định danh, chức danh, phòng ban, phân quyền vai trò hoặc đặt lại mật khẩu đăng nhập."
    />

    <div class="max-w-3xl">
        <x-admin.card class="!p-6">
            <form method="POST" action="{{ route('admin.users.update', $staffUser) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-admin.form-field name="name" label="Họ và tên *">
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name', $staffUser->name) }}"
                            required
                            class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white focus:border-[#1C69D4] focus:outline-none"
                        >
                    </x-admin.form-field>

                    <x-admin.form-field name="email" label="Email công vụ *">
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email', $staffUser->email) }}"
                            required
                            class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white focus:border-[#1C69D4] focus:outline-none"
                        >
                    </x-admin.form-field>

                    <x-admin.form-field name="phone" label="Số điện thoại nội bộ">
                        <input
                            type="text"
                            name="phone"
                            value="{{ old('phone', $staffUser->phone) }}"
                            placeholder="0901234567"
                            class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium tabular-nums text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                        >
                    </x-admin.form-field>

                    <x-admin.form-field name="role" label="Vai trò hệ thống *">
                        <x-ui.custom-select
                            name="role"
                            :value="(string) old('role', $staffUser->normalizedRole())"
                            :options="$roles"
                        />
                    </x-admin.form-field>

                    <x-admin.form-field name="job_title" label="Chức danh công việc">
                        <input
                            type="text"
                            name="job_title"
                            value="{{ old('job_title', $staffUser->job_title) }}"
                            placeholder="Chuyên viên Tư vấn Bán hàng"
                            class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                        >
                    </x-admin.form-field>

                    <x-admin.form-field name="department" label="Phòng ban / Khu vực">
                        <input
                            type="text"
                            name="department"
                            value="{{ old('department', $staffUser->department) }}"
                            placeholder="Khối Kinh doanh Showroom"
                            class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                        >
                    </x-admin.form-field>
                </div>

                <x-admin.form-field name="password" label="Đặt lại mật khẩu mới (bỏ trống nếu giữ nguyên)">
                    <input
                        type="password"
                        name="password"
                        minlength="8"
                        placeholder="Tối thiểu 8 ký tự nếu muốn đổi mật khẩu..."
                        class="h-10 w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 text-xs font-medium text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                    >
                </x-admin.form-field>

                <x-admin.form-field name="notes" label="Ghi chú quản trị">
                    <textarea
                        name="notes"
                        rows="3"
                        placeholder="Ghi chú nội bộ..."
                        class="w-full rounded-xl border border-white/10 bg-[#070A10] px-3.5 py-2.5 text-xs text-white placeholder:text-zinc-500 focus:border-[#1C69D4] focus:outline-none"
                    >{{ old('notes', $staffUser->notes) }}</textarea>
                </x-admin.form-field>

                <div class="flex justify-end border-t border-white/[0.08] pt-4">
                    <button
                        type="submit"
                        class="inline-flex h-10 items-center rounded-xl bg-[#1C69D4] px-6 text-xs font-bold text-white shadow-sm shadow-[#1C69D4]/25 transition-colors hover:bg-[#2575e6] cursor-pointer"
                    >
                        Lưu cập nhật hồ sơ
                    </button>
                </div>
            </form>
        </x-admin.card>
    </div>
</x-admin-layout>
