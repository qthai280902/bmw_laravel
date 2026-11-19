<?php

namespace App\Mail\Customer;

use App\Models\Appointment;
use App\Models\Product;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmationMail extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public const MODE_SYSTEM_RECEIVED = 'system_received';

    public const MODE_ADMIN_CONFIRMED = 'admin_confirmed';

    public string $queue = 'notifications';

    public function __construct(
        public Appointment $appointment,
        public string $mode = self::MODE_SYSTEM_RECEIVED,
        public ?string $confirmedByName = null,
        public ?string $confirmedByRole = null,
    ) {}

    public function isAdminConfirmed(): bool
    {
        return $this->mode === self::MODE_ADMIN_CONFIRMED;
    }

    public function envelope(): Envelope
    {
        $this->appointment->loadMissing(['product.category', 'user']);

        $subject = $this->isAdminConfirmed()
            ? "[BMW Concierge] XÁC NHẬN CHÍNH THỨC lịch hẹn #{$this->appointment->id} — Sẵn sàng đón tiếp Quý khách"
            : "[BMW Showroom] Đã tiếp nhận yêu cầu đặt lịch #{$this->appointment->id} — Chờ chuyên viên xác nhận";

        return new Envelope(
            subject: $subject,
            replyTo: [self::salesReplyToAddress()],
        );
    }

    public function content(): Content
    {
        $this->appointment->loadMissing(['product.category', 'product.primaryImage', 'product.images', 'user']);

        return new Content(
            view: 'emails.customer.appointment-confirmation',
            text: 'emails.customer.appointment-confirmation-text',
            with: [
                'mode' => $this->mode,
                'isAdminConfirmed' => $this->isAdminConfirmed(),
                'confirmedByName' => $this->confirmedByName,
                'confirmedByRole' => $this->confirmedByRole,
                'inlineImagePath' => $this->appointment->product?->localImagePath(),
                'fallbackBadgePng' => self::fallbackBadgePngBytes(),
            ],
        );
    }

    public static function salesReplyToAddress(): Address
    {
        $address = (string) (
            config('mail.showroom_admins.0')
            ?: config('mail.reply_to.address')
            ?: config('mail.from.address', 'advisor@bmw.com')
        );
        $name = (string) config('mail.reply_to.name', 'BMW Showroom Tư Vấn');

        return new Address($address, $name);
    }

    public static function fallbackBadgePngBytes(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            true
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-BMW-Mail-Kind' => 'appointment_confirmation',
                'X-BMW-Mail-Mode' => $this->mode,
                'X-BMW-Appointment-ID' => (string) $this->appointment->id,
            ],
        );
    }

    /**
     * @return array<int, Attachment|Attachable>
     */
    public function attachments(): array
    {
        $this->appointment->loadMissing(['product.category']);

        $attachments = [$this->appointment];
        $product = $this->appointment->product;

        if ($product instanceof Product) {
            $attachments[] = Attachment::fromData(
                fn (): string => $this->renderVehicleBrochureHtml($product),
                'BMW-'.$product->slug.'-Brochure.html'
            )->withMime('text/html');
        }

        return $attachments;
    }

    public function renderVehicleBrochureHtml(Product $product): string
    {
        $product->loadMissing('category');

        $productName = e((string) $product->name);
        $categoryName = e((string) ($product->category?->name ?? 'BMW Chính hãng'));
        $priceText = $product->price !== null
            ? number_format((int) $product->price, 0, ',', '.').' VNĐ'
            : 'Liên hệ Showroom';
        $depositText = ((int) ($product->deposit_amount ?? 0)) > 0
            ? number_format((int) $product->deposit_amount, 0, ',', '.').' VNĐ'
            : 'Thỏa thuận tại Showroom';
        $descriptionText = filled($product->description)
            ? e(trim(strip_tags((string) $product->description)))
            : 'Hồ sơ thông số kỹ thuật chính hãng được chuẩn hóa từ BMW Showroom Việt Nam.';

        $specRowsHtml = '';
        foreach ($product->translated_specs as $specLabel => $specValue) {
            $valueString = is_array($specValue) ? implode(', ', $specValue) : (string) $specValue;
            $specRowsHtml .= '<tr>'
                .'<td style="padding:12px 16px;border-bottom:1px solid #27272a;color:#a1a1aa;font-size:12px;text-transform:uppercase;letter-spacing:0.08em;width:42%;">'
                .e((string) $specLabel)
                .'</td>'
                .'<td style="padding:12px 16px;border-bottom:1px solid #27272a;color:#ffffff;font-weight:700;font-size:13px;">'
                .e($valueString)
                .'</td>'
                .'</tr>';
        }

        if ($specRowsHtml === '') {
            $specRowsHtml = '<tr><td colspan="2" style="padding:14px 16px;color:#a1a1aa;font-size:13px;">Thông số kỹ thuật chi tiết sẽ được Cố vấn BMW bàn giao trực tiếp tại Showroom.</td></tr>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>BMW Brochure — {$productName}</title>
</head>
<body style="margin:0;padding:32px 16px;background-color:#09090b;color:#ffffff;font-family:'Be Vietnam Pro','Inter',Arial,sans-serif;">
    <div style="max-width:680px;margin:0 auto;background-color:#121215;border:1px solid #27272a;border-radius:0px;">
        <div style="padding:28px 32px;background-color:#09090b;border-bottom:1px solid #27272a;">
            <div style="font-size:11px;font-weight:800;letter-spacing:0.3em;text-transform:uppercase;color:#1C69D4;">BMW TECHNICAL BROCHURE · {$categoryName}</div>
            <h1 style="margin:10px 0 0 0;font-size:24px;font-weight:900;text-transform:uppercase;letter-spacing:0.08em;color:#ffffff;">{$productName}</h1>
        </div>
        <div style="padding:28px 32px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#09090b;border:1px solid #27272a;margin-bottom:24px;">
                <tr>
                    <td style="padding:12px 16px;border-bottom:1px solid #27272a;color:#a1a1aa;font-size:12px;text-transform:uppercase;letter-spacing:0.08em;width:42%;">Giá niêm yết</td>
                    <td style="padding:12px 16px;border-bottom:1px solid #27272a;color:#1C69D4;font-weight:900;font-size:15px;">{$priceText}</td>
                </tr>
                <tr>
                    <td style="padding:12px 16px;color:#a1a1aa;font-size:12px;text-transform:uppercase;letter-spacing:0.08em;">Đặt cọc tham khảo</td>
                    <td style="padding:12px 16px;color:#ffffff;font-weight:700;font-size:13px;">{$depositText}</td>
                </tr>
            </table>
            <p style="margin:0 0 24px 0;color:#d4d4d8;font-size:14px;line-height:1.7;">{$descriptionText}</p>
            <div style="font-size:11px;font-weight:800;letter-spacing:0.25em;text-transform:uppercase;color:#1C69D4;margin-bottom:10px;">BẢNG THÔNG SỐ KỸ THUẬT</div>
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#09090b;border:1px solid #27272a;">
                {$specRowsHtml}
            </table>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
