<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Mail\AppointmentReminderMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Mail\Customer\GuestAppointmentChangeMail;
use App\Mail\Customer\PostTestDriveFollowupMail;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
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
     * Xem trước trực tiếp 5 mẫu email khách hàng của lịch hẹn trên trình duyệt cho Admin/Sales.
     */
    public function previewMail(Request $request, Appointment $appointment): Mailable
    {
        $appointment->loadMissing(['product.category', 'user']);

        $requestedMode = trim((string) $request->query('mode', AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED));

        return match ($requestedMode) {
            AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED => new AppointmentConfirmationMail(
                appointment: $appointment,
                mode: AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED,
                confirmedByName: $request->user()?->name,
                confirmedByRole: $request->user()?->roleLabel(),
            ),
            'guest_rescheduled' => $this->makeGuestChangePreviewMail($appointment),
            'reminder' => new AppointmentReminderMail($appointment),
            'followup' => new PostTestDriveFollowupMail($appointment),
            default => new AppointmentConfirmationMail(
                appointment: $appointment,
                mode: AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED,
                confirmedByName: $request->user()?->name,
                confirmedByRole: $request->user()?->roleLabel(),
            ),
        };
    }

    private function makeGuestChangePreviewMail(Appointment $appointment): GuestAppointmentChangeMail
    {
        $meta = is_array($appointment->meta_data) ? $appointment->meta_data : [];
        $previousDateLabel = isset($meta['previous_appointment_date']) && is_string($meta['previous_appointment_date']) && $meta['previous_appointment_date'] !== ''
            ? $meta['previous_appointment_date']
            : ($appointment->appointment_date?->copy()->subHours(2)->format('H:i — d/m/Y') ?? '09:00 — '.now()->addDay()->format('d/m/Y'));

        $reason = isset($meta['reschedule_reason']) && is_string($meta['reschedule_reason']) && $meta['reschedule_reason'] !== ''
            ? $meta['reschedule_reason']
            : 'Khách hàng chủ động điều chỉnh sang khung giờ phù hợp hơn qua đường dẫn Signed URL.';

        return new GuestAppointmentChangeMail(
            appointment: $appointment,
            action: GuestAppointmentChangeMail::ACTION_RESCHEDULE,
            previousDateLabel: $previousDateLabel,
            newDateLabel: $appointment->appointment_date?->format('H:i — d/m/Y'),
            previousStatusLabel: AppointmentStatus::Pending->label(),
            newStatusLabel: $appointment->statusLabel(),
            reason: $reason,
        );
    }
}
