<?php

namespace App\Services\Crm;

use App\Enums\AppointmentStatus;
use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\User;
use ArrayObject;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Context;
use Throwable;

class InteractionTimelineRecorder
{
    public const ACTOR_CUSTOMER = 'customer';

    public const ACTOR_SYSTEM = 'system';

    public const ACTOR_ADMIN = 'admin';

    public const MAX_EVENTS = 50;

    /**
     * Chuẩn hóa meta_data từ array, ArrayObject (AsArrayObject), Arrayable hoặc null sang mảng thuần.
     *
     * @return array<string, mixed>
     */
    public static function normalizeMetaArray(mixed $meta): array
    {
        if ($meta instanceof ArrayObject) {
            return $meta->getArrayCopy();
        }

        if ($meta instanceof Arrayable) {
            return $meta->toArray();
        }

        return is_array($meta) ? $meta : [];
    }

    /**
     * Build a structured timeline event payload, automatically inheriting trace_id from Laravel Context.
     *
     * @return array{
     *     actor_type: string,
     *     actor_label: string,
     *     title: string,
     *     detail: string,
     *     channel: string,
     *     occurred_at: string,
     *     occurred_at_iso: string,
     *     trace_id?: string
     * }
     */
    public static function makeEvent(
        string $actorType,
        string $actorLabel,
        string $title,
        ?string $detail = null,
        string $channel = 'CRM',
        ?CarbonInterface $occurredAt = null,
    ): array {
        $timestamp = $occurredAt ?? now();
        $normalizedActorType = in_array($actorType, [self::ACTOR_CUSTOMER, self::ACTOR_SYSTEM, self::ACTOR_ADMIN], true)
            ? $actorType
            : self::ACTOR_SYSTEM;

        $event = [
            'actor_type' => $normalizedActorType,
            'actor_label' => trim($actorLabel) !== '' ? trim($actorLabel) : self::defaultActorLabel($normalizedActorType),
            'title' => trim($title),
            'detail' => trim((string) ($detail ?? '')),
            'channel' => trim($channel) !== '' ? trim($channel) : 'CRM',
            'occurred_at' => $timestamp->format('H:i · d/m/Y'),
            'occurred_at_iso' => $timestamp->toIso8601String(),
        ];

        $traceId = Context::get('trace_id');
        if (is_string($traceId) && trim($traceId) !== '') {
            $event['trace_id'] = trim($traceId);
        }

        return $event;
    }

    /**
     * Append a timeline event into an existing meta_data array or ArrayObject without issuing extra SQL queries.
     *
     * @param  array<string, mixed>|ArrayObject<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public static function appendToMeta(
        array|ArrayObject $meta,
        string $actorType,
        string $actorLabel,
        string $title,
        ?string $detail = null,
        string $channel = 'CRM',
        ?CarbonInterface $occurredAt = null,
    ): array {
        $normalized = self::normalizeMetaArray($meta);
        $events = isset($normalized['timeline']) && is_array($normalized['timeline']) ? array_values($normalized['timeline']) : [];
        $events[] = self::makeEvent($actorType, $actorLabel, $title, $detail, $channel, $occurredAt);

        if (count($events) > self::MAX_EVENTS) {
            $events = array_slice($events, -self::MAX_EVENTS);
        }

        $normalized['timeline'] = $events;

        return $normalized;
    }

    /**
     * Record a timeline event on an Appointment or AccessoryOrder model.
     */
    public static function record(
        Model $model,
        string $actorType,
        string $actorLabel,
        string $title,
        ?string $detail = null,
        string $channel = 'CRM',
        ?CarbonInterface $occurredAt = null,
        bool $persist = true,
    ): void {
        $meta = self::normalizeMetaArray($model->getAttribute('meta_data'));
        $meta = self::appendToMeta($meta, $actorType, $actorLabel, $title, $detail, $channel, $occurredAt);

        $model->forceFill(['meta_data' => $meta]);

        if ($persist && $model->exists) {
            $model->saveQuietly();
        }
    }

    public static function adminActorLabel(?User $actor): string
    {
        if (! $actor instanceof User) {
            return 'Showroom BMW';
        }

        return "{$actor->roleLabel()} · {$actor->name}";
    }

    /**
     * Return full normalized 2-way timeline for an Appointment (auto-reconstructing fallback events for legacy records).
     *
     * @return list<array{
     *     actor_type: string,
     *     actor_label: string,
     *     title: string,
     *     detail: string,
     *     channel: string,
     *     occurred_at: string,
     *     occurred_at_iso: string
     * }>
     */
    public static function timelineForAppointment(Appointment $appointment): array
    {
        $meta = self::normalizeMetaArray($appointment->meta_data);
        $rawTimeline = isset($meta['timeline']) && is_array($meta['timeline']) ? $meta['timeline'] : [];

        if ($rawTimeline !== []) {
            return self::normalizeEvents($rawTimeline);
        }

        $events = [];
        $createdAt = $appointment->created_at ?? now();
        $channel = filled($appointment->ai_conversation_id) || filled($appointment->ai_visitor_id) ? 'AI Chat' : 'Web';
        $targetLabel = $appointment->product?->name
            ?? ($appointment->servicePackageLabel() ?: $appointment->typeLabel());

        $events[] = self::makeEvent(
            actorType: self::ACTOR_CUSTOMER,
            actorLabel: 'Khách hàng · '.$appointment->customer_name,
            title: "Gửi yêu cầu {$appointment->typeLabel()}",
            detail: "Khách đặt lịch cho {$targetLabel}"
                .($appointment->appointment_date ? ' vào lúc '.$appointment->appointment_date->format('H:i · d/m/Y') : '')
                .'.',
            channel: $channel,
            occurredAt: $createdAt,
        );

        if ($appointment->confirmation_email_sent_at) {
            $events[] = self::makeEvent(
                actorType: self::ACTOR_SYSTEM,
                actorLabel: 'Hệ thống tự động',
                title: 'Gửi thư tiếp nhận ban đầu',
                detail: 'Hệ thống tự động gửi email xác nhận đã tiếp nhận yêu cầu kèm liên kết quản lý Signed URL.',
                channel: 'Email',
                occurredAt: $appointment->confirmation_email_sent_at,
            );
        }

        if ($guestConfirmedAt = self::parseMetaTimestamp($meta['guest_confirmed_at'] ?? null)) {
            $events[] = self::makeEvent(
                actorType: self::ACTOR_CUSTOMER,
                actorLabel: 'Khách hàng · '.$appointment->customer_name,
                title: 'Khách tự xác nhận tham dự lịch hẹn',
                detail: 'Khách hàng đã bấm nút xác nhận tham dự trực tiếp qua đường dẫn Signed URL.',
                channel: 'Signed Link',
                occurredAt: $guestConfirmedAt,
            );
        }

        if ($rescheduledAt = self::parseMetaTimestamp($meta['rescheduled_at'] ?? null)) {
            $reason = trim((string) ($meta['reschedule_reason'] ?? ''));
            $previousLabel = trim((string) ($meta['previous_appointment_date'] ?? ''));
            $currentLabel = $appointment->appointment_date?->format('H:i · d/m/Y') ?? 'khung giờ mới';
            $detail = $previousLabel !== ''
                ? "Đổi lịch hẹn: {$previousLabel} → {$currentLabel}."
                : "Khách hàng cập nhật sang khung giờ {$currentLabel}.";
            if ($reason !== '') {
                $detail .= " Ghi chú: {$reason}";
            }

            $events[] = self::makeEvent(
                actorType: self::ACTOR_CUSTOMER,
                actorLabel: 'Khách hàng · '.$appointment->customer_name,
                title: 'Khách tự đổi ngày/giờ hẹn',
                detail: $detail,
                channel: 'Signed Link',
                occurredAt: $rescheduledAt,
            );
        }

        if ($officialSentAt = self::parseMetaTimestamp($meta['official_confirmation_sent_at'] ?? null)) {
            $confirmedBy = trim((string) ($meta['confirmed_by_name'] ?? ''));
            $events[] = self::makeEvent(
                actorType: self::ACTOR_ADMIN,
                actorLabel: $confirmedBy !== '' ? "Chuyên viên · {$confirmedBy}" : 'Admin / Showroom',
                title: 'Duyệt lịch & gửi Thư Xác Nhận VIP Pass',
                detail: 'Chuyên viên Showroom đã xác nhận chính thức suất đón tiếp và phát hành Thẻ đón tiếp VIP qua email.',
                channel: 'Email',
                occurredAt: $officialSentAt,
            );
        }

        if ($appointment->reminder_sent_at) {
            $events[] = self::makeEvent(
                actorType: self::ACTOR_SYSTEM,
                actorLabel: 'Hệ thống tự động',
                title: 'Gửi thư nhắc lịch trước 24 giờ',
                detail: 'Tự động gửi email đếm ngược 24h kèm hướng dẫn lộ trình và giấy tờ cần mang theo.',
                channel: 'Email',
                occurredAt: $appointment->reminder_sent_at,
            );
        }

        if ($appointment->followup_email_sent_at) {
            $events[] = self::makeEvent(
                actorType: self::ACTOR_SYSTEM,
                actorLabel: 'Hệ thống tự động',
                title: 'Lên lịch gửi thư hậu mãi & dự toán trả góp (sau 2h)',
                detail: 'Tự động gửi lời cảm ơn sau trải nghiệm kèm bảng ước tính tài chính tham khảo.',
                channel: 'Email',
                occurredAt: $appointment->followup_email_sent_at,
            );
        }

        if ($autoCancelledAt = self::parseMetaTimestamp($meta['auto_cancelled_at'] ?? null)) {
            $events[] = self::makeEvent(
                actorType: self::ACTOR_SYSTEM,
                actorLabel: 'Hệ thống tự động',
                title: 'Tự động hủy lịch treo quá hạn 24 giờ',
                detail: trim((string) ($meta['auto_cancelled_reason'] ?? 'Lịch hẹn quá hạn 24h chưa xác nhận được hệ thống tự động đóng.')),
                channel: 'CRM',
                occurredAt: $autoCancelledAt,
            );
        } elseif ($cancelledAt = self::parseMetaTimestamp($meta['cancelled_at'] ?? null)) {
            $reason = trim((string) ($meta['cancel_reason'] ?? ''));
            $events[] = self::makeEvent(
                actorType: self::ACTOR_CUSTOMER,
                actorLabel: 'Khách hàng · '.$appointment->customer_name,
                title: 'Khách tự hủy lịch hẹn',
                detail: $reason !== '' ? "Lý do: {$reason}" : 'Khách hàng đã hủy lịch hẹn qua đường dẫn Signed URL.',
                channel: 'Signed Link',
                occurredAt: $cancelledAt,
            );
        } elseif (
            $appointment->status instanceof AppointmentStatus
            && in_array($appointment->status, [AppointmentStatus::Confirmed, AppointmentStatus::Completed, AppointmentStatus::Cancelled], true)
            && ! $officialSentAt
            && $appointment->updated_at
        ) {
            $events[] = self::makeEvent(
                actorType: self::ACTOR_ADMIN,
                actorLabel: 'Admin / Showroom',
                title: "Cập nhật trạng thái: {$appointment->status->label()}",
                detail: 'Hồ sơ lịch hẹn đã được điều phối viên cập nhật trạng thái xử lý trên CRM.',
                channel: 'CRM',
                occurredAt: $appointment->updated_at,
            );
        }

        return self::normalizeEvents($events);
    }

    /**
     * Return full normalized 2-way timeline for an AccessoryOrder (with fallback for legacy rows).
     *
     * @return list<array{
     *     actor_type: string,
     *     actor_label: string,
     *     title: string,
     *     detail: string,
     *     channel: string,
     *     occurred_at: string,
     *     occurred_at_iso: string
     * }>
     */
    public static function timelineForAccessoryOrder(AccessoryOrder $order): array
    {
        $meta = self::normalizeMetaArray($order->meta_data);
        $rawTimeline = isset($meta['timeline']) && is_array($meta['timeline']) ? $meta['timeline'] : [];

        if ($rawTimeline !== []) {
            return self::normalizeEvents($rawTimeline);
        }

        $events = [];
        $productName = $order->product?->name ?? 'Phụ kiện chính hãng BMW';
        $channel = filled($order->ai_visitor_id) ? 'AI Chat' : 'Web';

        $events[] = self::makeEvent(
            actorType: self::ACTOR_CUSTOMER,
            actorLabel: 'Khách hàng · '.$order->customer_name,
            title: "Đặt đơn phụ kiện #ACC-{$order->id}",
            detail: "Đặt mua {$order->quantity} × {$productName} — Giao tới {$order->customer_address}.",
            channel: $channel,
            occurredAt: $order->created_at ?? now(),
        );

        if (filled($order->customer_email)) {
            $events[] = self::makeEvent(
                actorType: self::ACTOR_SYSTEM,
                actorLabel: 'Hệ thống tự động',
                title: 'Gửi email tiếp nhận đơn phụ kiện',
                detail: "Đã gửi xác nhận tiếp nhận đơn hàng tới {$order->customer_email}.",
                channel: 'Email',
                occurredAt: $order->created_at ?? now(),
            );
        }

        if ($order->confirmed_at) {
            $events[] = self::makeEvent(
                actorType: self::ACTOR_ADMIN,
                actorLabel: 'Admin / Showroom',
                title: 'Xác nhận đơn phụ kiện',
                detail: 'Nhân sự Showroom đã xác nhận tồn kho và tiếp nhận giao nhận đơn phụ kiện.',
                channel: 'CRM',
                occurredAt: $order->confirmed_at,
            );
        }

        if ($order->status !== AccessoryOrder::STATUS_PENDING && $order->updated_at) {
            $events[] = self::makeEvent(
                actorType: self::ACTOR_ADMIN,
                actorLabel: 'Admin / Showroom',
                title: "Trạng thái hiện tại: {$order->statusLabel()}",
                detail: filled($order->admin_notes)
                    ? "Ghi chú nội bộ: {$order->admin_notes}"
                    : 'Đơn hàng đã được cập nhật trạng thái trên hệ thống CRM.',
                channel: 'CRM',
                occurredAt: $order->updated_at,
            );
        }

        return self::normalizeEvents($events);
    }

    /**
     * @param  array<int, mixed>  $rawEvents
     * @return list<array{
     *     actor_type: string,
     *     actor_label: string,
     *     title: string,
     *     detail: string,
     *     channel: string,
     *     occurred_at: string,
     *     occurred_at_iso: string
     * }>
     */
    private static function normalizeEvents(array $rawEvents): array
    {
        $normalized = [];

        foreach ($rawEvents as $item) {
            if (! is_array($item)) {
                continue;
            }

            $actorType = in_array($item['actor_type'] ?? '', [self::ACTOR_CUSTOMER, self::ACTOR_SYSTEM, self::ACTOR_ADMIN], true)
                ? (string) $item['actor_type']
                : self::ACTOR_SYSTEM;

            $iso = is_string($item['occurred_at_iso'] ?? null) ? $item['occurred_at_iso'] : '';
            $formatted = is_string($item['occurred_at'] ?? null) && $item['occurred_at'] !== ''
                ? $item['occurred_at']
                : ($iso !== '' ? (self::parseMetaTimestamp($iso)?->format('H:i · d/m/Y') ?? '') : '');

            $normalizedItem = [
                'actor_type' => $actorType,
                'actor_label' => trim((string) ($item['actor_label'] ?? self::defaultActorLabel($actorType))),
                'title' => trim((string) ($item['title'] ?? 'Cập nhật hồ sơ')),
                'detail' => trim((string) ($item['detail'] ?? '')),
                'channel' => trim((string) ($item['channel'] ?? 'CRM')),
                'occurred_at' => $formatted,
                'occurred_at_iso' => $iso,
            ];

            if (is_string($item['trace_id'] ?? null) && trim($item['trace_id']) !== '') {
                $normalizedItem['trace_id'] = trim($item['trace_id']);
            }

            $normalized[] = $normalizedItem;
        }

        usort($normalized, static function (array $a, array $b): int {
            return strcmp($a['occurred_at_iso'], $b['occurred_at_iso']);
        });

        return $normalized;
    }

    private static function defaultActorLabel(string $actorType): string
    {
        return match ($actorType) {
            self::ACTOR_CUSTOMER => 'Khách hàng',
            self::ACTOR_ADMIN => 'Admin / Showroom',
            default => 'Hệ thống tự động',
        };
    }

    private static function parseMetaTimestamp(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
