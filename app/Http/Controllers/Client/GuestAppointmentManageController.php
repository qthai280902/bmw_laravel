<?php

namespace App\Http\Controllers\Client;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class GuestAppointmentManageController extends Controller
{
    public function show(Appointment $appointment): View
    {
        $appointment->loadMissing(['product.category', 'user']);

        return view('appointments.guest-manage', [
            'appointment' => $appointment,
            'updateUrl' => $appointment->temporaryManageUpdateUrl(),
        ]);
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', Rule::in(['confirm', 'reschedule', 'cancel'])],
            'new_appointment_date' => ['nullable', 'string'],
            'new_date' => ['nullable', 'date'],
            'new_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $action = $validated['action'];
        $cleanReason = isset($validated['reason']) ? trim(strip_tags((string) $validated['reason'])) : '';
        $meta = is_array($appointment->meta_data) ? $appointment->meta_data : [];

        if ($action === 'confirm') {
            if ($appointment->status === AppointmentStatus::Cancelled) {
                return back()->with('error', 'Lịch hẹn này đã bị hủy trước đó. Quý khách vui lòng đặt lịch mới.');
            }

            if ($appointment->status === AppointmentStatus::Completed) {
                return back()->with('error', 'Lịch hẹn này đã hoàn thành.');
            }

            if ($appointment->status === AppointmentStatus::Pending) {
                $meta['guest_confirmed_at'] = now()->toIso8601String();
                $appointment->forceFill([
                    'status' => AppointmentStatus::Confirmed->value,
                    'meta_data' => $meta,
                ])->save();
            }

            return back()->with('success', "Cảm ơn Quý khách đã xác nhận lịch hẹn #{$appointment->id}. Showroom BMW sẵn sàng đón tiếp.");
        }

        if ($action === 'reschedule') {
            if (in_array($appointment->status, [AppointmentStatus::Cancelled, AppointmentStatus::Completed], true)) {
                return back()->with('error', 'Lịch hẹn đã hoàn thành hoặc đã hủy nên không thể đổi giờ trực tuyến. Quý khách vui lòng đặt lịch hẹn mới.');
            }

            $rawDateTime = trim((string) ($validated['new_appointment_date'] ?? ''));

            if ($rawDateTime === '' && ! empty($validated['new_date']) && ! empty($validated['new_time'])) {
                $rawDateTime = trim((string) $validated['new_date']).' '.trim((string) $validated['new_time']);
            }

            if ($rawDateTime === '') {
                throw ValidationException::withMessages([
                    'new_appointment_date' => 'Vui lòng chọn đầy đủ ngày và khung giờ hẹn mới.',
                ]);
            }

            try {
                $newDate = Carbon::parse($rawDateTime);
            } catch (Throwable) {
                throw ValidationException::withMessages([
                    'new_appointment_date' => 'Định dạng ngày giờ hẹn mới không hợp lệ.',
                ]);
            }

            if ($newDate->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'new_appointment_date' => 'Thời gian hẹn mới phải lớn hơn thời điểm hiện tại. Vui lòng chọn ngày hoặc khung giờ tiếp theo.',
                ]);
            }

            $meta['rescheduled_at'] = now()->toIso8601String();
            if ($cleanReason !== '') {
                $meta['reschedule_reason'] = $cleanReason;
            }

            $appointment->forceFill([
                'appointment_date' => $newDate,
                'reminder_sent_at' => null,
                'meta_data' => $meta,
            ])->save();

            return back()->with('success', "Đã cập nhật thời gian mới cho lịch hẹn #{$appointment->id}: ".$newDate->format('H:i — d/m/Y').'.');
        }

        if ($appointment->status === AppointmentStatus::Completed) {
            return back()->with('error', 'Lịch hẹn đã hoàn thành nên không thể hủy.');
        }

        $meta['cancelled_at'] = now()->toIso8601String();
        if ($cleanReason !== '') {
            $meta['cancel_reason'] = $cleanReason;
        }

        $appointment->forceFill([
            'status' => AppointmentStatus::Cancelled->value,
            'meta_data' => $meta,
        ])->save();

        return back()->with('success', "Lịch hẹn #{$appointment->id} đã được hủy theo yêu cầu của Quý khách.");
    }
}
