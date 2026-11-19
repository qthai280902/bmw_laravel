<?php

namespace App\Http\Controllers\Admin;

use App\Events\StaffAccountUpdated;
use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\User;
use App\Services\Admin\AdminNotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Danh sách nhân sự nội bộ Showroom & Nhật ký hoạt động gần đây.
     */
    public function index(): View
    {
        $users = User::query()->latest()->paginate(20);
        $activityLogs = AdminActivityLog::query()
            ->latest('id')
            ->limit(15)
            ->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => User::roles(),
            'activityLogs' => $activityLogs,
        ]);
    }

    /**
     * Tạo mới tài khoản nhân sự nội bộ.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(User::roleValues())],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $user = User::query()->create([
            'name' => trim($validated['name']),
            'email' => $email,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        StaffAccountUpdated::dispatch(
            $user,
            'staff.created',
            "Tạo nhân sự mới {$user->name} ({$user->email}) với vai trò {$user->roleLabel()}.",
            $request->user()
        );

        return back()->with('success', "Đã tạo nhân sự {$user->name} ({$user->roleLabel()}).");
    }

    /**
     * Cập nhật vai trò nhân sự (chặn tự hạ quyền Quản Lý của chính mình).
     */
    public function updateRole(Request $request, User $user, AdminNotificationDispatcher $dispatcher): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(User::roleValues())],
        ]);

        $actingUser = $request->user();
        if ($actingUser && $actingUser->is($user) && $validated['role'] !== User::ROLE_ADMIN) {
            return back()
                ->withErrors(['role' => 'Không thể tự hạ vai trò Quản Lý của chính mình xuống Sales hoặc Marketing.'])
                ->with('error', 'Không thể tự hạ vai trò Quản Lý của chính mình xuống Sales hoặc Marketing.');
        }

        $oldRoleLabel = $user->roleLabel();

        $user->update([
            'role' => $validated['role'],
        ]);

        $dispatcher->forgetDigestCache((int) $user->id);

        StaffAccountUpdated::dispatch(
            $user->fresh() ?? $user,
            'staff.role_updated',
            "Đổi vai trò nhân sự {$user->name} ({$user->email}) từ {$oldRoleLabel} sang {$user->roleLabel()}.",
            $actingUser
        );

        return back()->with('success', "Đã cập nhật vai trò cho {$user->name} thành {$user->roleLabel()}.");
    }
}
