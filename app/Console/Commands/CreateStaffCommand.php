<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Cache\ShowroomCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function Laravel\Prompts\callout;
use function Laravel\Prompts\form;
use function Laravel\Prompts\table;

class CreateStaffCommand extends Command
{
    protected $signature = 'showroom:create-staff
        {--name= : Họ và tên nhân sự}
        {--email= : Email đăng nhập nội bộ (@bmw.com)}
        {--role= : Vai trò (admin, sales, marketing)}
        {--password= : Mật khẩu khởi tạo (tối thiểu 8 ký tự)}
        {--super-admin : Cấp quyền Top Super Admin (chỉ áp dụng khi role là admin)}';

    protected $description = 'Khởi tạo tài khoản nhân sự nội bộ BMW Showroom (Admin / Sales / Marketing) bằng giao diện Laravel Prompts.';

    public function handle(ShowroomCacheService $cacheService): int
    {
        $name = trim((string) ($this->option('name') ?? ''));
        $email = strtolower(trim((string) ($this->option('email') ?? '')));
        $role = strtolower(trim((string) ($this->option('role') ?? '')));
        $password = (string) ($this->option('password') ?? '');
        $isSuperAdmin = (bool) $this->option('super-admin');

        $roleOptions = [
            User::ROLE_SALES => 'Chuyên viên Kinh doanh (Sales) — Xử lý lịch hẹn & đơn phụ kiện được giao',
            User::ROLE_MARKETING => 'Chuyên viên Nội dung (Marketing) — Quản trị bài viết & chiến dịch',
            User::ROLE_ADMIN => 'Quản trị viên (Admin) — Điều phối vận hành Showroom CRM',
        ];

        if ($name === '' || $email === '' || $role === '' || $password === '') {
            $answers = form()
                ->text(
                    label: 'Họ và tên nhân sự BMW',
                    placeholder: 'VD: Trần Minh Khôi',
                    default: $name,
                    required: 'Vui lòng nhập họ và tên nhân sự.',
                    name: 'name',
                )
                ->text(
                    label: 'Email nội bộ (@bmw.com)',
                    placeholder: 'VD: sales.khoi@bmw.com',
                    default: $email,
                    required: 'Vui lòng nhập email đăng nhập.',
                    validate: function (string $value): ?string {
                        $normalized = strtolower(trim($value));
                        if (! filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                            return 'Định dạng email không hợp lệ.';
                        }
                        if (! str_ends_with($normalized, '@bmw.com')) {
                            return 'Email nhân sự nội bộ phải sử dụng tên miền @bmw.com.';
                        }
                        if (User::query()->whereRaw('LOWER(email) = ?', [$normalized])->exists()) {
                            return 'Email này đã tồn tại trong hệ thống.';
                        }

                        return null;
                    },
                    name: 'email',
                )
                ->select(
                    label: 'Chọn phân quyền vai trò',
                    options: $roleOptions,
                    default: in_array($role, array_keys($roleOptions), true) ? $role : User::ROLE_SALES,
                    name: 'role',
                )
                ->password(
                    label: 'Mật khẩu khởi tạo (tối thiểu 8 ký tự)',
                    required: 'Vui lòng nhập mật khẩu.',
                    validate: static fn (string $value): ?string => strlen($value) < 8
                        ? 'Mật khẩu phải có ít nhất 8 ký tự.'
                        : null,
                    name: 'password',
                )
                ->submit();

            $name = trim((string) ($answers['name'] ?? ''));
            $email = strtolower(trim((string) ($answers['email'] ?? '')));
            $role = strtolower(trim((string) ($answers['role'] ?? User::ROLE_SALES)));
            $password = (string) ($answers['password'] ?? '');
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'ends_with:@bmw.com', 'unique:users,email'],
            'role' => ['required', 'string', Rule::in(array_keys($roleOptions))],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $grantSuperAdmin = $role === User::ROLE_ADMIN && $isSuperAdmin;

        $staff = User::query()->create([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'is_super_admin' => $grantSuperAdmin,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $cacheService->flushSalesRoster();

        $summaryRows = [
            ['ID nhân sự', (string) $staff->id],
            ['Họ và tên', $staff->name],
            ['Email đăng nhập', $staff->email],
            ['Vai trò', $staff->roleLabel()],
            ['Phân quyền Kế toán', $grantSuperAdmin ? 'Top Super Admin' : 'Tiêu chuẩn'],
        ];

        if ($this->input->isInteractive()) {
            table(['Thuộc tính tài khoản', 'Giá trị'], $summaryRows);
            callout("Đã khởi tạo tài khoản nhân sự {$staff->email} thành công.", 'BMW Staff Provisioned');
        } else {
            $this->table(['Thuộc tính tài khoản', 'Giá trị'], $summaryRows);
            $this->info("Đã khởi tạo tài khoản nhân sự {$staff->email} thành công.");
        }

        return self::SUCCESS;
    }
}
