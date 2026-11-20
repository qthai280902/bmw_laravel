<?php

namespace App\Http\Controllers\Client;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Mail\Customer\GuestAppointmentChangeMail;
use App\Models\Appointment;
use App\Services\Crm\InteractionTimelineRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        $previousDateLabel = $appointment->appointment_date?->format('H:i — d/m/Y');
        $previousStatusLabel = $appointment->statusLabel();

        if ($action === 'confirm') {
            if ($appointment->status === AppointmentStatus::Cancelled) {
                return back()->with('error', 'Lịch hẹn này đã bị hủy trước đó. Quý khách vui lòng đặt lịch mới.');
            }

            if ($appointment->status === AppointmentStatus::Completed) {
                return back()->with('error', 'Lịch hẹn này đã hoàn thành.');
            }

            if ($appointment->status === AppointmentStatus::Pending) {
                $meta['guest_confirmed_at'] = now()->toIso8601String();
                $meta = InteractionTimelineRecorder::appendToMeta(
                    meta: $meta,
                    actorType: InteractionTimelineRecorder::ACTOR_CUSTOMER,
                    actorLabel: 'Khách hàng · '.$appointment->customer_name,
                    title: 'Khách tự xác nhận tham dự lịch hẹn',
                    detail: 'Khách hàng đã bấm nút xác nhận tham dự trực tiếp qua đường dẫn Signed URL.',
                    channel: 'Signed Link',
                );

                $appointment->forceFill([
                    'status' => AppointmentStatus::Confirmed->value,
                    'meta_data' => $meta,
                ])->save();

                $this->dispatchGuestChangeMail(
                    appointment: $appointment,
                    action: GuestAppointmentChangeMail::ACTION_CONFIRM,
                    previousDateLabel: $previousDateLabel,
                    newDateLabel: $previousDateLabel,
                    previousStatusLabel: $previousStatusLabel,
                    newStatusLabel: AppointmentStatus::Confirmed->label(),
                    reason: $cleanReason !== '' ? $cleanReason : null,
                );
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

            $newDateLabel = $newDate->format('H:i — d/m/Y');
            $meta['rescheduled_at'] = now()->toIso8601String();
            $meta['previous_appointment_date'] = $previousDateLabel;
            if ($cleanReason !== '') {
                $meta['reschedule_reason'] = $cleanReason;
            }

            $timelineDetail = $previousDateLabel
                ? "Đổi lịch hẹn: {$previousDateLabel} → {$newDateLabel}."
                : "Đổi sang khung giờ mới: {$newDateLabel}.";
            if ($cleanReason !== '') {
                $timelineDetail .= " Ghi chú: {$cleanReason}";
            }

            $meta = InteractionTimelineRecorder::appendToMeta(
                meta: $meta,
                actorType: InteractionTimelineRecorder::ACTOR_CUSTOMER,
                actorLabel: 'Khách hàng · '.$appointment->customer_name,
                title: 'Khách tự đổi ngày/giờ hẹn',
                detail: $timelineDetail,
                channel: 'Signed Link',
            );

            $appointment->forceFill([
                'appointment_date' => $newDate,
                'reminder_sent_at' => null,
                'meta_data' => $meta,
            ])->save();

            $this->dispatchGuestChangeMail(
                appointment: $appointment,
                action: GuestAppointmentChangeMail::ACTION_RESCHEDULE,
                previousDateLabel: $previousDateLabel,
                newDateLabel: $newDateLabel,
                previousStatusLabel: $previousStatusLabel,
                newStatusLabel: $appointment->statusLabel(),
                reason: $cleanReason !== '' ? $cleanReason : null,
            );

            return back()->with('success', "Đã cập nhật thời gian mới cho lịch hẹn #{$appointment->id}: ".$newDateLabel.'.');
        }

        if ($appointment->isTerminalStatus()) {
            return back()->with('error', $appointment->status === AppointmentStatus::Completed
                ? 'Lịch hẹn đã hoàn thành nên không thể hủy.'
                : 'Lịch hẹn này đã được hủy trước đó.');
        }

        $meta['cancelled_at'] = now()->toIso8601String();
        if ($cleanReason !== '') {
            $meta['cancel_reason'] = $cleanReason;
        }

        $meta = InteractionTimelineRecorder::appendToMeta(
            meta: $meta,
            actorType: InteractionTimelineRecorder::ACTOR_CUSTOMER,
            actorLabel: 'Khách hàng · '.$appointment->customer_name,
            title: 'Khách tự hủy lịch hẹn',
            detail: $cleanReason !== ''
                ? "Lý do khách cung cấp: {$cleanReason}"
                : 'Khách hàng đã hủy lịch hẹn qua đường dẫn Signed URL.',
            channel: 'Signed Link',
        );

        $appointment->forceFill([
            'status' => AppointmentStatus::Cancelled->value,
            'meta_data' => $meta,
        ])->save();

        $this->dispatchGuestChangeMail(
            appointment: $appointment,
            action: GuestAppointmentChangeMail::ACTION_CANCEL,
            previousDateLabel: $previousDateLabel,
            newDateLabel: $previousDateLabel,
            previousStatusLabel: $previousStatusLabel,
            newStatusLabel: AppointmentStatus::Cancelled->label(),
            reason: $cleanReason !== '' ? $cleanReason : null,
        );

        return back()->with('success', "Lịch hẹn #{$appointment->id} đã được hủy theo yêu cầu của Quý khách.");
    }

    private function dispatchGuestChangeMail(
        Appointment $appointment,
        string $action,
        ?string $previousDateLabel,
        ?string $newDateLabel,
        ?string $previousStatusLabel,
        ?string $newStatusLabel,
        ?string $reason,
    ): void {
        $appointment->loadMissing(['product.category', 'user']);
        $recipientEmail = trim((string) ($appointment->user?->email ?? $appointment->guest_email ?? ''));

        if ($recipientEmail === '' || ! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($recipientEmail)->send(new GuestAppointmentChangeMail(
                appointment: $appointment,
                action: $action,
                previousDateLabel: $previousDateLabel,
                newDateLabel: $newDateLabel,
                previousStatusLabel: $previousStatusLabel,
                newStatusLabel: $newStatusLabel,
                reason: $reason,
            ));

            InteractionTimelineRecorder::record(
                model: $appointment,
                actorType: InteractionTimelineRecorder::ACTOR_SYSTEM,
                actorLabel: 'Hệ thống tự động',
                title: 'Gửi email xác nhận thay đổi lịch hẹn cho khách',
                detail: "Đã gửi thư đối chiếu cập nhật lịch hẹn tới {$recipientEmail}.",
                channel: 'Email',
            );
        } catch (Throwable $exception) {
            Log::warning('Failed sending guest appointment change email.', [
                'appointment_id' => $appointment->id,
                'action' => $action,
                'exception' => $exception::class,
            ]);
        }
    }
}
