<?php

namespace App\Services\Mail;

use App\Models\CustomerInstallmentPlan;
use App\Models\EmailTemplate;

class EmailTemplateRenderer
{
    /**
     * Metadata & fallback defaults for all supported BMW Email Template variables.
     *
     * @return array<string, array{label: string, sample: string, fallback: string}>
     */
    public static function variableCatalog(): array
    {
        return [
            'customer_name' => [
                'label' => 'Tên khách hàng',
                'sample' => 'Nguyễn Minh Anh',
                'fallback' => 'Quý khách',
            ],
            'vehicle_name' => [
                'label' => 'Mẫu xe BMW',
                'sample' => 'BMW X5 xDrive40i M Sport',
                'fallback' => 'dòng xe BMW',
            ],
            'advisor_name' => [
                'label' => 'Chuyên viên phụ trách',
                'sample' => 'Trần Quốc Bảo (Sales BMW)',
                'fallback' => 'Đội ngũ Chuyên viên BMW Showroom',
            ],
            'bank_name' => [
                'label' => 'Ngân hàng trả góp',
                'sample' => 'Techcombank BMW Financial',
                'fallback' => 'Ngân hàng đối tác BMW Financial',
            ],
            'interest_rate' => [
                'label' => 'Lãi suất (%/năm)',
                'sample' => '7.8%/năm',
                'fallback' => 'theo biểu lãi suất hiện hành',
            ],
            'monthly_payment' => [
                'label' => 'Tiền góp hàng tháng',
                'sample' => '42.500.000 VNĐ',
                'fallback' => 'theo lịch thanh toán hợp đồng',
            ],
            'next_due_date' => [
                'label' => 'Ngày đến hạn kỳ tới',
                'sample' => '15/12/2026',
                'fallback' => 'theo lịch kỳ tiếp theo',
            ],
            'showroom_hotline' => [
                'label' => 'Hotline Showroom',
                'sample' => '1900 123 456',
                'fallback' => '1900 123 456',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sampleVariables(): array
    {
        $samples = [];
        foreach (self::variableCatalog() as $key => $meta) {
            $samples[$key] = $meta['sample'];
        }

        return $samples;
    }

    /**
     * Build a normalized variable bag for a specific customer and optional active installment plan.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, string>
     */
    public function buildVariables(
        ?string $customerName = null,
        ?string $vehicleName = null,
        ?string $advisorName = null,
        ?CustomerInstallmentPlan $installmentPlan = null,
        array $overrides = [],
    ): array {
        $catalog = self::variableCatalog();

        $resolvedVehicle = trim((string) ($vehicleName ?: ($installmentPlan?->relationLoaded('product') ? $installmentPlan->product?->name : '')));
        $resolvedBank = trim((string) ($installmentPlan?->bank_name ?? ''));
        $resolvedRate = $installmentPlan !== null
            ? rtrim(rtrim(number_format((float) $installmentPlan->interest_rate, 2, '.', ''), '0'), '.').'%/năm'
            : '';
        $resolvedMonthly = $installmentPlan !== null && $installmentPlan->monthly_payment > 0
            ? number_format((int) $installmentPlan->monthly_payment, 0, ',', '.').' VNĐ'
            : '';
        $resolvedNextDue = $installmentPlan?->next_due_date !== null
            ? $installmentPlan->next_due_date->format('d/m/Y')
            : '';

        $base = [
            'customer_name' => trim((string) $customerName),
            'vehicle_name' => $resolvedVehicle,
            'advisor_name' => trim((string) $advisorName),
            'bank_name' => $resolvedBank,
            'interest_rate' => $resolvedRate,
            'monthly_payment' => $resolvedMonthly,
            'next_due_date' => $resolvedNextDue,
            'showroom_hotline' => '1900 123 456',
        ];

        $merged = [];
        foreach ($catalog as $key => $meta) {
            $overrideVal = isset($overrides[$key]) ? trim((string) $overrides[$key]) : '';
            $candidate = $overrideVal !== '' ? $overrideVal : ($base[$key] ?? '');
            $merged[$key] = $candidate !== '' ? $candidate : $meta['fallback'];
        }

        foreach ($overrides as $key => $value) {
            if (! array_key_exists($key, $merged) && is_string($key)) {
                $merged[$key] = trim((string) $value);
            }
        }

        return $merged;
    }

    /**
     * Interpolate {{variable}} tokens cleanly and guarantee zero raw {{...}} leaks.
     *
     * @param  array<string, string>  $variables
     */
    public function interpolate(?string $text, array $variables): string
    {
        $content = (string) ($text ?? '');
        if ($content === '') {
            return '';
        }

        $catalog = self::variableCatalog();

        $replaced = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}/u',
            static function (array $matches) use ($variables, $catalog): string {
                $key = $matches[1];
                if (isset($variables[$key]) && trim((string) $variables[$key]) !== '') {
                    return trim((string) $variables[$key]);
                }

                if (isset($catalog[$key]['fallback'])) {
                    return $catalog[$key]['fallback'];
                }

                return '';
            },
            $content
        );

        return trim((string) ($replaced ?? $content));
    }

    /**
     * Render all fields of an EmailTemplate with interpolated variables.
     *
     * @param  array<string, string>  $variables
     * @return array{
     *     subject: string,
     *     eyebrow: string,
     *     heading: string,
     *     body_content: string,
     *     paragraphs: list<string>,
     *     highlight_title: string,
     *     highlight_content: string,
     *     cta_label: string,
     *     cta_url: string,
     *     accent_color: string,
     *     category_label: string
     * }
     */
    public function renderTemplate(
        EmailTemplate $template,
        array $variables,
        ?string $subjectOverride = null,
        ?string $bodyOverride = null,
    ): array {
        $subject = $this->interpolate(
            filled($subjectOverride) ? $subjectOverride : $template->subject,
            $variables
        );
        $eyebrow = $this->interpolate($template->eyebrow, $variables);
        $heading = $this->interpolate($template->heading, $variables);
        $body = $this->interpolate(
            filled($bodyOverride) ? $bodyOverride : $template->body_content,
            $variables
        );
        $highlightTitle = $this->interpolate($template->highlight_title, $variables);
        $highlightContent = $this->interpolate($template->highlight_content, $variables);
        $ctaLabel = $this->interpolate($template->cta_label, $variables);
        $ctaUrl = $this->interpolate($template->cta_url, $variables);

        $accentColor = trim((string) $template->accent_color);
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $accentColor)) {
            $accentColor = '#1C69D4';
        }

        $paragraphs = array_values(array_filter(
            array_map('trim', preg_split('/\R{2,}/u', $body) ?: [$body]),
            static fn (string $chunk): bool => $chunk !== ''
        ));

        return [
            'subject' => $subject !== '' ? $subject : '[BMW Showroom] Thông báo dành cho Quý khách',
            'eyebrow' => $eyebrow !== '' ? $eyebrow : 'BMW CLIENT RELATIONS',
            'heading' => $heading !== '' ? $heading : $subject,
            'body_content' => $body,
            'paragraphs' => $paragraphs,
            'highlight_title' => $highlightTitle,
            'highlight_content' => $highlightContent,
            'cta_label' => $ctaLabel,
            'cta_url' => $ctaUrl,
            'accent_color' => $accentColor,
            'category_label' => $template->categoryLabel(),
        ];
    }
}
