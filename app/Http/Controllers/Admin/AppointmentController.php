<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Rule;

class AppointmentController extends Controller
{
    /**
     * Danh sách tất cả lịch hẹn.
     */
    public function index()
    {
        return view('admin.appointments.index');
    }

    /**
     * Cập nhật trạng thái lịch hẹn.
     */
    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(AppointmentStatus::class)],
        ]);

        $appointment->transitionStatusWithAutomation(
            AppointmentStatus::from($validated['status']),
            $request->user()
        );

        return back()->with('success', 'Đã cập nhật trạng thái lịch hẹn thành công.');
    }

    /**
     * Xem trước mẫu email tiếp nhận / xác nhận chính thức trực tiếp trên trình duyệt cho Admin.
     */
    public function previewMail(Request $request, Appointment $appointment): AppointmentConfirmationMail
    {
        $appointment->loadMissing(['product.category', 'user']);

        $requestedMode = trim((string) $request->query('mode', AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED));
        $mode = $requestedMode === AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED
            ? AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED
            : AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED;

        return new AppointmentConfirmationMail(
            appointment: $appointment,
            mode: $mode,
            confirmedByName: $request->user()?->name,
            confirmedByRole: $request->user()?->roleLabel(),
        );
    }
}
