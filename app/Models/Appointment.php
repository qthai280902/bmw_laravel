<?php

namespace App\Models;

use App\Collections\AppointmentCollection;
use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Events\AppointmentStatusChanged;
use App\Exceptions\InvalidStatusTransitionException;
use App\Mail\Customer\AppointmentCompletedThankYouMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Mail\Customer\PostTestDriveFollowupMail;
use App\Models\Concerns\HasCrmArchiveAndAttribution;
use App\Notifications\Admin\GuestRescheduledAppointmentNotification;
use App\Services\Crm\DocumentCodeGenerator;
use App\Services\Crm\InteractionTimelineRecorder;
use App\Services\Mail\GmailQuotaGuard;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

#[CollectedBy(AppointmentCollection::class)]
class Appointment extends Model implements Attachable
{
    use HasCrmArchiveAndAttribution;
    use HasFactory;

    public const LEAD_PRIORITY_HOT = 'hot';

    public const LEAD_PRIORITY_WARM = 'warm';

    public const LEAD_PRIORITY_NORMAL = 'normal';

    public const OUTCOME_PURCHASED = 'purchased';

    public const OUTCOME_VISITED_NO_PURCHASE = 'visited_no_purchase';

    /**
     * Sensitive internal attributes hidden from JSON/array serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'customer_internal_code',
        'daily_sequence_code',
        'meta_data',
    ];

    protected $fillable = [
        'customer_order_code',
        'daily_sequence_code',
        'customer_internal_code',
        'user_id',
        'assigned_to',
        'guest_name',
        'guest_email',
        'guest_phone',
        'product_id',
        'type',
        'appointment_date',
        'status',
        'notes',
        'meta_data',
        'showroom',
        'ai_visitor_id',
        'ai_conversation_id',
        'ai_chat_summary',
        'lead_priority',
        'lead_intent',
        'ai_lead_note',
        'confirmation_email_sent_at',
        'reminder_sent_at',
        'followup_email_sent_at',
        'archived_at',
        'archived_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => AppointmentType::class,
            'status' => AppointmentStatus::class,
            'appointment_date' => 'datetime',
            'meta_data' => AsArrayObject::class,
            'confirmation_email_sent_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'followup_email_sent_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static function (Appointment $appointment): void {
            app(DocumentCodeGenerator::class)->populateMissingCodes($appointment);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedSales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withDefault([
            'name' => 'Chưa phân công',
        ]);
    }

    public function isServiceBooking(): bool
    {
        $type = $this->type instanceof AppointmentType
            ? $this->type
            : AppointmentType::tryFrom((string) $this->type);

        return in_array($type, [
            AppointmentType::Maintenance,
            AppointmentType::Detailing,
            AppointmentType::CarWash,
        ], true);
    }

    public function publicReferenceCode(): string
    {
        if (is_string($this->customer_order_code) && trim($this->customer_order_code) !== '') {
            return trim($this->customer_order_code);
        }

        $prefix = $this->isServiceBooking() ? 'SRV' : 'BMW';
        $yymmdd = ($this->created_at ?? now())->copy()->timezone(DocumentCodeGenerator::TIMEZONE)->format('ymd');

        return "{$prefix}-{$yymmdd}-REF".str_pad((string) ($this->id ?? 1), 2, '0', STR_PAD_LEFT);
    }

    public function internalVoucherCode(): string
    {
        if (is_string($this->daily_sequence_code) && trim($this->daily_sequence_code) !== '') {
            return trim($this->daily_sequence_code);
        }

        $yymmdd = ($this->created_at ?? now())->copy()->timezone(DocumentCodeGenerator::TIMEZONE)->format('ymd');

        return "CT-{$yymmdd}-".str_pad((string) ($this->id ?? 1), 3, '0', STR_PAD_LEFT);
    }

    public function internalCustomerCode(): string
    {
        if (is_string($this->customer_internal_code) && trim($this->customer_internal_code) !== '') {
            return trim($this->customer_internal_code);
        }

        $digits = DocumentCodeGenerator::normalizePhoneDigits($this->guest_phone ?? $this->user?->phone ?? null);
        $lastFour = substr(str_pad($digits, 4, '0', STR_PAD_LEFT), -4);

        return "KH-{$lastFour}-01";
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function aiChatSession(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'ai_conversation_id');
    }

    public function typeLabel(): string
    {
        return $this->type instanceof AppointmentType ? $this->type->label() : 'Lịch hẹn BMW';
    }

    public function statusLabel(): string
    {
        return $this->status instanceof AppointmentStatus ? $this->status->label() : (string) $this->status;
    }

    /**
     * Trả về danh sách các trạng thái kế tiếp hợp lệ theo State Machine 1 chiều.
     *
     * @return list<AppointmentStatus>
     */
    public static function allowedTransitionsFrom(AppointmentStatus|string $status): array
    {
        $resolved = $status instanceof AppointmentStatus
            ? $status
            : (AppointmentStatus::tryFrom((string) $status) ?? AppointmentStatus::Pending);

        return match ($resolved) {
            AppointmentStatus::Pending => [AppointmentStatus::Confirmed, AppointmentStatus::Cancelled],
            AppointmentStatus::Confirmed => [AppointmentStatus::Completed, AppointmentStatus::Cancelled],
            AppointmentStatus::Completed, AppointmentStatus::Cancelled => [],
        };
    }

    /**
     * @return list<AppointmentStatus>
     */
    public function allowedNextStatuses(): array
    {
        $current = $this->status instanceof AppointmentStatus
            ? $this->status
            : AppointmentStatus::from((string) $this->status);

        return self::allowedTransitionsFrom($current);
    }

    public function isTerminalStatus(): bool
    {
        $current = $this->status instanceof AppointmentStatus
            ? $this->status
            : AppointmentStatus::tryFrom((string) $this->status);

        return in_array($current, [AppointmentStatus::Completed, AppointmentStatus::Cancelled], true);
    }

    public function canTransitionTo(AppointmentStatus $newStatus): bool
    {
        return in_array($newStatus, $this->allowedNextStatuses(), true);
    }

    /**
     * Các lựa chọn trạng thái hiển thị trên Inline Table Select (chỉ gồm trạng thái hiện tại + các bước kế tiếp hợp lệ).
     *
     * @return array<string, string>
     */
    public function allowedStatusOptions(): array
    {
        $current = $this->status instanceof AppointmentStatus
            ? $this->status
            : AppointmentStatus::from((string) $this->status);

        $options = [$current->value => $current->label()];

        foreach ($this->allowedNextStatuses() as $next) {
            $options[$next->value] = $next->label();
        }

        return $options;
    }

    public function getCustomerNameAttribute(): string
    {
        return (string) ($this->user?->name ?: $this->guest_name ?: 'Quý khách');
    }

    public function getCustomerPhoneAttribute(): string
    {
        return (string) ($this->guest_phone ?: $this->user?->phone ?: '');
    }

    public function getCustomerEmailAttribute(): string
    {
        return (string) ($this->user?->email ?: $this->guest_email ?: '');
    }

    public function leadPriorityLabel(): string
    {
        return match ($this->lead_priority) {
            self::LEAD_PRIORITY_HOT => 'HOT LEAD',
            self::LEAD_PRIORITY_WARM => 'TIỀM NĂNG',
            default => 'THAM KHẢO',
        };
    }

    public function leadPriorityColorClass(): string
    {
        return match ($this->lead_priority) {
            self::LEAD_PRIORITY_HOT => 'border-rose-500/40 bg-rose-500/15 text-rose-400',
            self::LEAD_PRIORITY_WARM => 'border-[#1C69D4]/40 bg-[#1C69D4]/15 text-[#70A7FF]',
            default => 'border-zinc-700 bg-zinc-900 text-zinc-400',
        };
    }

    public function leadIntentLabel(): string
    {
        return match ($this->lead_intent) {
            'ready_to_buy' => 'Sẵn sàng chốt mua',
            'test_drive_experience' => 'Trải nghiệm lái thử',
            'price_comparison' => 'So sánh & báo giá',
            'aftersales_service' => 'Dịch vụ sau bán hàng',
            default => 'Tìm hiểu thông tin',
        };
    }

    public function toMailAttachment(): Attachment
    {
        return Attachment::fromData(
            fn (): string => $this->toIcsContent(),
            "BMW-Appointment-{$this->id}.ics"
        )->withMime('text/calendar');
    }

    public function toIcsContent(): string
    {
        $this->loadMissing(['product', 'user']);

        $startAt = ($this->appointment_date ?? now()->addDay())->copy()->utc();
        $endAt = $startAt->copy()->addHour();
        $stamp = now()->utc()->format('Ymd\THis\Z');

        $typeLabel = $this->type instanceof AppointmentType ? $this->type->label() : 'Lịch hẹn BMW';
        $productName = $this->product?->name
            ?? ($this->meta_data['customer_car_model'] ?? 'Tư vấn tại BMW Showroom');
        $customerName = $this->user?->name ?? $this->guest_name ?? 'Quý khách';
        $location = $this->showroom
            ?? ($this->meta_data['showroom'] ?? 'BMW Showroom Chính Hãng Việt Nam');

        $summary = $this->escapeIcsText("BMW Showroom: {$typeLabel} - {$productName}");
        $description = $this->escapeIcsText(implode("\n", array_filter([
            "Mã tham chiếu: {$this->publicReferenceCode()}",
            "Khách hàng: {$customerName}",
            filled($this->guest_phone) ? "SĐT: {$this->guest_phone}" : null,
            "Dòng xe / Dịch vụ: {$productName}",
            filled($this->notes) ? "Ghi chú: {$this->notes}" : null,
        ])));
        $escapedLocation = $this->escapeIcsText((string) $location);

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//BMW Showroom Vietnam//Appointments//VI',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            "UID:bmw-appointment-{$this->id}@bmw-showroom.local",
            "DTSTAMP:{$stamp}",
            'DTSTART:'.$startAt->format('Ymd\THis\Z'),
            'DTEND:'.$endAt->format('Ymd\THis\Z'),
            "SUMMARY:{$summary}",
            "DESCRIPTION:{$description}",
            "LOCATION:{$escapedLocation}",
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);
    }

    /**
     * Canonical list of official BMW Showroom vehicle care & service packages.
     *
     * @return array<string, string>
     */
    public static function servicePackageOptions(): array
    {
        return [
            'Bảo dưỡng định kỳ tiêu chuẩn BMW (Standard Maintenance)' => 'Bảo dưỡng định kỳ tiêu chuẩn BMW (Standard Maintenance)',
            'Phủ Ceramic & Hiệu chỉnh bề mặt sơn (BMW Paint Protection)' => 'Phủ Ceramic & Hiệu chỉnh bề mặt sơn (BMW Paint Protection)',
            'Chăm sóc nội thất da Merino & Vệ sinh khoang máy chuyên sâu' => 'Chăm sóc nội thất da Merino & Vệ sinh khoang máy chuyên sâu',
            'Kiểm tra tổng quát & Chẩn đoán điện tử ISTA chính hãng' => 'Kiểm tra tổng quát & Chẩn đoán điện tử ISTA chính hãng',
        ];
    }

    public function servicePackageLabel(): ?string
    {
        $meta = InteractionTimelineRecorder::normalizeMetaArray($this->meta_data);
        $raw = $meta['service_package'] ?? null;

        return is_string($raw) && trim($raw) !== '' ? trim($raw) : null;
    }

    public function currentManageVersion(): int
    {
        $meta = InteractionTimelineRecorder::normalizeMetaArray($this->meta_data);

        return max(1, (int) ($meta['manage_version'] ?? 1));
    }

    public function hasPendingRescheduleReview(): bool
    {
        if ($this->isTerminalStatus()) {
            return false;
        }

        $meta = InteractionTimelineRecorder::normalizeMetaArray($this->meta_data);

        return (bool) ($meta['reschedule_pending_review'] ?? false);
    }

    public function completionOutcome(): ?string
    {
        $meta = InteractionTimelineRecorder::normalizeMetaArray($this->meta_data);
        $outcome = $meta['completion_outcome'] ?? null;

        return in_array($outcome, [self::OUTCOME_PURCHASED, self::OUTCOME_VISITED_NO_PURCHASE], true)
            ? $outcome
            : null;
    }

    public function completionOutcomeLabel(): ?string
    {
        return match ($this->completionOutcome()) {
            self::OUTCOME_PURCHASED => 'Đã mua xe',
            self::OUTCOME_VISITED_NO_PURCHASE => 'Chưa mua xe',
            default => null,
        };
    }

    public function careVoucherCode(): ?string
    {
        $meta = InteractionTimelineRecorder::normalizeMetaArray($this->meta_data);
        $code = $meta['care_voucher_code'] ?? null;

        return is_string($code) && trim($code) !== '' ? trim($code) : null;
    }

    public function careVoucherExpiresLabel(): ?string
    {
        $meta = InteractionTimelineRecorder::normalizeMetaArray($this->meta_data);
        $raw = $meta['care_voucher_expires_at'] ?? null;

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y');
        } catch (Throwable) {
            return null;
        }
    }

    public function markRescheduleNotificationsAsRead(): void
    {
        try {
            $unread = DatabaseNotification::query()
                ->where('type', GuestRescheduledAppointmentNotification::class)
                ->whereNull('read_at')
                ->get();

            foreach ($unread as $notification) {
                $data = is_array($notification->data) ? $notification->data : [];
                if ((int) ($data['appointment_id'] ?? 0) === (int) $this->id) {
                    $notification->markAsRead();
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Failed marking reschedule notifications as read.', [
                'appointment_id' => $this->id,
                'exception' => $exception::class,
            ]);
        }
    }

    public function temporaryManageUrl(): string
    {
        return URL::temporarySignedRoute(
            'appointments.guest.manage',
            now()->addDays(14),
            [
                'appointment' => $this->id,
                'v' => $this->currentManageVersion(),
            ]
        );
    }

    public function temporaryManageUpdateUrl(): string
    {
        return URL::temporarySignedRoute(
            'appointments.guest.update',
            now()->addDays(14),
            [
                'appointment' => $this->id,
                'v' => $this->currentManageVersion(),
            ]
        );
    }

    /**
     * Calculate reference installment plan (20% down, 80% loan, 60 months, 8.5%/year).
     *
     * @return array{
     *     price: int,
     *     down_payment_percent: int,
     *     down_payment: int,
     *     loan_percent: int,
     *     loan_amount: int,
     *     term_months: int,
     *     annual_interest_rate: float,
     *     monthly_payment: int
     * }|null
     */
    public function financialEstimate(): ?array
    {
        $this->loadMissing('product');

        $price = (int) ($this->product?->price ?? 0);

        if ($price <= 0) {
            return null;
        }

        $downPaymentPercent = 20;
        $loanPercent = 80;
        $termMonths = 60;
        $annualRate = 8.5;

        $downPayment = (int) round($price * ($downPaymentPercent / 100));
        $loanAmount = max(0, $price - $downPayment);
        $monthlyRate = ($annualRate / 100) / 12;
        $factor = pow(1 + $monthlyRate, $termMonths);
        $monthlyPayment = $factor > 1
            ? (int) round($loanAmount * ($monthlyRate * $factor) / ($factor - 1))
            : (int) round($loanAmount / $termMonths);

        return [
            'price' => $price,
            'down_payment_percent' => $downPaymentPercent,
            'down_payment' => $downPayment,
            'loan_percent' => $loanPercent,
            'loan_amount' => $loanAmount,
            'term_months' => $termMonths,
            'annual_interest_rate' => $annualRate,
            'monthly_payment' => $monthlyPayment,
        ];
    }

    public function schedulePostTestDriveFollowup(): bool
    {
        $this->loadMissing(['product.category', 'user']);

        if ($this->followup_email_sent_at !== null || $this->completionOutcome() !== null) {
            return false;
        }

        $recipientEmail = trim((string) ($this->user?->email ?? $this->guest_email ?? ''));

        if ($recipientEmail === '' || ! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            $sendAt = now()->addHours(2);
            $mail = (new PostTestDriveFollowupMail($this))->delay($sendAt);

            Mail::to($recipientEmail)->later($sendAt, $mail);

            $meta = InteractionTimelineRecorder::normalizeMetaArray($this->meta_data);
            $meta = InteractionTimelineRecorder::appendToMeta(
                meta: $meta,
                actorType: InteractionTimelineRecorder::ACTOR_SYSTEM,
                actorLabel: 'Hệ thống tự động',
                title: 'Lên lịch gửi thư hậu mãi & dự toán trả góp (sau 2h)',
                detail: "Hệ thống hẹn gửi email cảm ơn và bảng dự toán tài chính tới {$recipientEmail} sau 2 giờ.",
                channel: 'Email',
            );

            $this->forceFill([
                'followup_email_sent_at' => now(),
                'meta_data' => $meta,
            ])->saveQuietly();

            return true;
        } catch (Throwable $exception) {
            Log::error('Failed scheduling post-test-drive followup email.', [
                'appointment_id' => $this->id,
                'recipient' => $recipientEmail,
                'trace_id' => Context::get('trace_id'),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'trace' => substr($exception->getTraceAsString(), 0, 1200),
            ]);

            return false;
        }
    }

    public function sendOfficialConfirmationEmail(?User $actor = null): bool
    {
        if ($this->isTerminalStatus()) {
            return false;
        }

        $this->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'user', 'assignedSales']);

        $recipientEmail = trim((string) ($this->user?->email ?? $this->guest_email ?? ''));

        if ($recipientEmail === '' || ! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $advisor = AppointmentConfirmationMail::resolveAdvisorDisplay($this, $actor?->name, $actor?->roleLabel());

        try {
            Mail::to($recipientEmail)->send(new AppointmentConfirmationMail(
                appointment: $this,
                mode: AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED,
                confirmedByName: $advisor['name'],
                confirmedByRole: $advisor['role'],
            ));

            $meta = InteractionTimelineRecorder::normalizeMetaArray($this->meta_data);
            $meta['official_confirmation_sent_at'] = now()->toIso8601String();
            if ($actor) {
                $meta['confirmed_by_name'] = $actor->name;
            }
            $meta = InteractionTimelineRecorder::appendToMeta(
                meta: $meta,
                actorType: InteractionTimelineRecorder::ACTOR_ADMIN,
                actorLabel: InteractionTimelineRecorder::adminActorLabel($actor),
                title: 'Gửi Thư Xác Nhận Chính Thức (Thẻ VIP Pass)',
                detail: "Đã phát hành Thẻ đón tiếp VIP và gửi email xác nhận chính thức tới {$recipientEmail} (Phiên bản link v={$this->currentManageVersion()}).",
                channel: 'Email',
            );
            $this->forceFill([
                'meta_data' => $meta,
            ])->saveQuietly();

            return true;
        } catch (Throwable $exception) {
            Log::error('Failed sending official admin confirmation email.', [
                'appointment_id' => $this->id,
                'recipient' => $recipientEmail,
                'trace_id' => Context::get('trace_id'),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'trace' => substr($exception->getTraceAsString(), 0, 1200),
            ]);

            return false;
        }
    }

    public function sendCompletedThankYouEmail(
        string $outcome,
        ?string $voucherCode = null,
        ?string $voucherExpiresLabel = null,
    ): bool {
        $this->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'user', 'assignedSales']);

        $recipientEmail = trim((string) ($this->user?->email ?? $this->guest_email ?? ''));

        if ($recipientEmail === '' || ! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            $quotaGuard = app(GmailQuotaGuard::class);
            if (! $quotaGuard->canSendMarketingEmail()) {
                Log::warning('Sending transactional AppointmentCompletedThankYouMail while Gmail soft limit is reached.', [
                    'appointment_id' => $this->id,
                    'outcome' => $outcome,
                    'sent_today' => $quotaGuard->getSentCountForDate(),
                    'soft_limit' => $quotaGuard->dailySoftLimit(),
                ]);
            }

            Mail::to($recipientEmail)->send(new AppointmentCompletedThankYouMail(
                appointment: $this,
                outcome: $outcome,
                voucherCode: $voucherCode,
                voucherExpiresLabel: $voucherExpiresLabel,
            ));

            $meta = InteractionTimelineRecorder::normalizeMetaArray($this->meta_data);
            $meta['completed_thank_you_sent_at'] = now()->toIso8601String();
            $title = $outcome === self::OUTCOME_PURCHASED
                ? 'Gửi thư chúc mừng sở hữu xe & Voucher BMW Care 1 tháng'
                : 'Gửi thư cảm ơn khách hàng đã tham quan & trải nghiệm';
            $detail = $outcome === self::OUTCOME_PURCHASED
                ? "Đã gửi email chúc mừng tới {$recipientEmail} kèm mã đặc quyền {$voucherCode} (hiệu lực đến {$voucherExpiresLabel})."
                : "Đã gửi email tri ân khách hàng đã ghé Showroom trải nghiệm tới {$recipientEmail} (không kèm voucher khuyến mãi).";

            $meta = InteractionTimelineRecorder::appendToMeta(
                meta: $meta,
                actorType: InteractionTimelineRecorder::ACTOR_SYSTEM,
                actorLabel: 'Hệ thống tự động',
                title: $title,
                detail: $detail,
                channel: 'Email',
            );

            $this->forceFill([
                'followup_email_sent_at' => now(),
                'meta_data' => $meta,
            ])->saveQuietly();

            return true;
        } catch (Throwable $exception) {
            Log::error('Failed sending post-visit completed thank-you email.', [
                'appointment_id' => $this->id,
                'recipient' => $recipientEmail,
                'outcome' => $outcome,
                'trace_id' => Context::get('trace_id'),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'trace' => substr($exception->getTraceAsString(), 0, 1200),
            ]);

            return false;
        }
    }

    public function transitionStatusWithAutomation(
        AppointmentStatus $newStatus,
        ?User $actor = null,
        ?string $completionOutcome = null,
    ): bool {
        $normalizedOutcome = in_array($completionOutcome, [self::OUTCOME_PURCHASED, self::OUTCOME_VISITED_NO_PURCHASE], true)
            ? $completionOutcome
            : null;

        $transitionResult = DB::transaction(function () use ($newStatus, $actor, $normalizedOutcome): ?array {
            /** @var self $locked */
            $locked = static::query()
                ->whereKey($this->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentStatus = $locked->status instanceof AppointmentStatus
                ? $locked->status
                : AppointmentStatus::from((string) $locked->status);
            $oldStatus = $currentStatus->value;
            $oldLabel = $currentStatus->label();

            if ($oldStatus === $newStatus->value) {
                $this->setRawAttributes($locked->getAttributes(), true);
                $this->meta_data = $locked->meta_data;

                return null;
            }

            if (! $locked->canTransitionTo($newStatus)) {
                if ($locked->isTerminalStatus()) {
                    throw InvalidStatusTransitionException::forTerminalStatus($oldLabel);
                }

                throw InvalidStatusTransitionException::forTransition(
                    $oldLabel,
                    $newStatus->label(),
                    'Vui lòng duyệt xác nhận hồ sơ trước khi hoàn tất.'
                );
            }

            $meta = InteractionTimelineRecorder::normalizeMetaArray($locked->meta_data);
            $voucherCode = null;
            $voucherExpiresLabel = null;

            if ($newStatus === AppointmentStatus::Cancelled) {
                $meta['reschedule_pending_review'] = false;
                $meta = InteractionTimelineRecorder::withCancellationFromActor($meta, $actor);
            }

            if ($newStatus === AppointmentStatus::Completed) {
                $meta['reschedule_pending_review'] = false;

                if ($normalizedOutcome !== null && ! $locked->isServiceBooking()) {
                    $meta['completion_outcome'] = $normalizedOutcome;

                    if ($normalizedOutcome === self::OUTCOME_PURCHASED) {
                        $voucherCode = 'BMW-CARE-'.strtoupper(Str::random(6));
                        $expiresAt = now('Asia/Ho_Chi_Minh')->addMonth();
                        $voucherExpiresLabel = $expiresAt->format('d/m/Y');

                        $meta['purchased_at'] = now()->toIso8601String();
                        $meta['care_voucher_code'] = $voucherCode;
                        $meta['care_voucher_expires_at'] = $expiresAt->toIso8601String();
                    } else {
                        $meta['visited_at'] = now()->toIso8601String();
                        $meta['care_voucher_code'] = null;
                        $meta['care_voucher_expires_at'] = null;
                    }
                }
            }

            $timelineDetail = match (true) {
                $newStatus === AppointmentStatus::Confirmed => 'Chuyên viên Showroom đã duyệt lịch hẹn và kích hoạt quy trình gửi Thẻ đón tiếp VIP.',
                $newStatus === AppointmentStatus::Completed && $normalizedOutcome === self::OUTCOME_PURCHASED => "Khách hàng đã ký hợp đồng mua xe tại Showroom. Cấp đặc quyền BMW Care 1 tháng ({$voucherCode}, hạn {$voucherExpiresLabel}).",
                $newStatus === AppointmentStatus::Completed && $normalizedOutcome === self::OUTCOME_VISITED_NO_PURCHASE => 'Khách hàng đã hoàn tất buổi tham quan / lái thử tại Showroom (chưa phát sinh giao dịch mua xe).',
                default => "Cập nhật trạng thái xử lý trên bảng điều phối CRM sang {$newStatus->label()}.",
            };

            $meta = InteractionTimelineRecorder::appendToMeta(
                meta: $meta,
                actorType: InteractionTimelineRecorder::ACTOR_ADMIN,
                actorLabel: InteractionTimelineRecorder::adminActorLabel($actor),
                title: "Chuyển trạng thái: {$oldLabel} → {$newStatus->label()}",
                detail: $timelineDetail,
                channel: 'CRM',
            );

            $locked->update([
                'status' => $newStatus->value,
                'meta_data' => $meta,
            ]);

            $this->setRawAttributes($locked->getAttributes(), true);
            $this->meta_data = $locked->meta_data;

            return [
                'oldStatus' => $oldStatus,
                'fresh' => $locked->fresh(['product', 'user', 'assignedSales']) ?? $locked,
                'voucherCode' => $voucherCode,
                'voucherExpiresLabel' => $voucherExpiresLabel,
                'isService' => $locked->isServiceBooking(),
            ];
        });

        if ($transitionResult === null) {
            return false;
        }

        if (in_array($newStatus, [AppointmentStatus::Cancelled, AppointmentStatus::Completed], true)) {
            $this->markRescheduleNotificationsAsRead();
        }

        event(new AppointmentStatusChanged(
            appointment: $transitionResult['fresh'],
            oldStatus: $transitionResult['oldStatus'],
            newStatus: $newStatus->value,
            actor: $actor,
        ));

        if ($newStatus === AppointmentStatus::Confirmed && $transitionResult['oldStatus'] !== AppointmentStatus::Confirmed->value && $actor !== null) {
            $this->sendOfficialConfirmationEmail($actor);
        }

        if ($newStatus === AppointmentStatus::Completed) {
            if ($normalizedOutcome !== null && ! $transitionResult['isService']) {
                $this->sendCompletedThankYouEmail(
                    outcome: $normalizedOutcome,
                    voucherCode: $transitionResult['voucherCode'],
                    voucherExpiresLabel: $transitionResult['voucherExpiresLabel'],
                );
            } else {
                $this->schedulePostTestDriveFollowup();
            }
        }

        return true;
    }

    private function escapeIcsText(string $value): string
    {
        $normalized = str_replace(["\r\n", "\r", "\n"], "\n", $value);

        return str_replace(
            ['\\', ';', ',', "\n"],
            ['\\\\', '\;', '\,', '\n'],
            $normalized
        );
    }
}
