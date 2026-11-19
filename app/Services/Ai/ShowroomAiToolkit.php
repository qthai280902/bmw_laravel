<?php

namespace App\Services\Ai;

use App\Models\Appointment;
use App\Models\Product;
use Illuminate\Support\Str;

class ShowroomAiToolkit
{
    /**
     * Calculate verified installment financing figures directly from DB product price.
     *
     * @return array{
     *     product_id: int,
     *     product_name: string,
     *     product_url: string,
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
    public function calculateFinancialEstimate(int $productId, int $downPaymentPercent = 20, int $months = 60): ?array
    {
        if ($productId <= 0) {
            return null;
        }

        $product = Product::query()->active()->find($productId);

        if (! $product || (int) ($product->price ?? 0) <= 0) {
            return null;
        }

        $downPct = max(10, min(80, $downPaymentPercent));
        $termMonths = max(12, min(84, $months));

        if ($downPct === 20 && $termMonths === 60) {
            $appointment = new Appointment(['product_id' => $product->id]);
            $appointment->setRelation('product', $product);
            $estimate = $appointment->financialEstimate();
        } else {
            $price = (int) $product->price;
            $loanPercent = max(0, 100 - $downPct);
            $downPayment = (int) round($price * ($downPct / 100));
            $loanAmount = max(0, $price - $downPayment);
            $annualRate = 8.5;
            $monthlyRate = ($annualRate / 100) / 12;
            $factor = pow(1 + $monthlyRate, $termMonths);
            $monthlyPayment = $factor > 1
                ? (int) round($loanAmount * ($monthlyRate * $factor) / ($factor - 1))
                : (int) round($loanAmount / $termMonths);

            $estimate = [
                'price' => $price,
                'down_payment_percent' => $downPct,
                'down_payment' => $downPayment,
                'loan_percent' => $loanPercent,
                'loan_amount' => $loanAmount,
                'term_months' => $termMonths,
                'annual_interest_rate' => $annualRate,
                'monthly_payment' => $monthlyPayment,
            ];
        }

        if (! is_array($estimate)) {
            return null;
        }

        return [
            'product_id' => (int) $product->id,
            'product_name' => (string) $product->name,
            'product_url' => route('products.show', $product->slug, false),
            ...$estimate,
        ];
    }

    /**
     * Check live warehouse stock status for an active BMW accessory.
     *
     * @return array{
     *     found: bool,
     *     product_id: int,
     *     name?: string,
     *     slug?: string,
     *     price?: int,
     *     stock: int,
     *     in_stock: bool,
     *     status_label: string,
     *     order_url?: string
     * }
     */
    public function checkAccessoryStock(int $productId): array
    {
        if ($productId <= 0) {
            return [
                'found' => false,
                'product_id' => $productId,
                'stock' => 0,
                'in_stock' => false,
                'status_label' => 'ID phụ kiện không hợp lệ',
            ];
        }

        $product = Product::query()
            ->active()
            ->with('category')
            ->find($productId);

        if (! $product || ! $product->isAccessory()) {
            return [
                'found' => false,
                'product_id' => $productId,
                'stock' => 0,
                'in_stock' => false,
                'status_label' => 'Không tìm thấy phụ kiện chính hãng đang kinh doanh',
            ];
        }

        $stockCount = $product->stock === null ? 99 : max(0, (int) $product->stock);
        $inStock = $product->stock === null || $stockCount > 0;

        return [
            'found' => true,
            'product_id' => (int) $product->id,
            'name' => (string) $product->name,
            'slug' => (string) $product->slug,
            'price' => (int) ($product->price ?? 0),
            'stock' => $stockCount,
            'in_stock' => $inStock,
            'status_label' => $inStock
                ? ($product->stock === null ? 'Sẵn hàng tại Showroom' : "Còn {$stockCount} sản phẩm trong kho")
                : 'Tạm hết hàng',
            'order_url' => route('accessory-orders.create', $product->slug, false),
        ];
    }

    /**
     * Return official BMW vehicle care & service packages with duration, price range, and booking link.
     *
     * @return list<array{
     *     badge: string,
     *     title: string,
     *     type: string,
     *     duration: string,
     *     price: string,
     *     booking_url: string
     * }>
     */
    public function listServicePackages(): array
    {
        $packages = [
            [
                'badge' => 'RELIABILITY · CBS STANDARD',
                'title' => 'Bảo dưỡng định kỳ tiêu chuẩn BMW (Standard Maintenance)',
                'type' => 'maintenance',
                'duration' => '90 — 120 phút',
                'price' => 'Từ 3.800.000 VNĐ',
            ],
            [
                'badge' => 'PRECISION · CERAMIC 9H+',
                'title' => 'Phủ Ceramic & Hiệu chỉnh bề mặt sơn (BMW Paint Protection)',
                'type' => 'detailing',
                'duration' => '1 — 2 ngày làm việc',
                'price' => 'Từ 14.500.000 VNĐ',
            ],
            [
                'badge' => 'PURITY · INTERIOR & ENGINE BAY',
                'title' => 'Chăm sóc nội thất da Merino & Vệ sinh khoang máy chuyên sâu',
                'type' => 'detailing',
                'duration' => '4 — 6 giờ',
                'price' => 'Từ 4.900.000 VNĐ',
            ],
            [
                'badge' => 'INTELLIGENCE · BMW ISTA',
                'title' => 'Kiểm tra tổng quát & Chẩn đoán điện tử ISTA chính hãng',
                'type' => 'maintenance',
                'duration' => '60 — 90 phút',
                'price' => 'Từ 1.500.000 VNĐ',
            ],
        ];

        return array_map(static fn (array $package): array => [
            ...$package,
            'booking_url' => route('appointments.create', [
                'type' => 'service',
                'service_package' => $package['title'],
            ], false),
        ], $packages);
    }

    /**
     * Ground prompt automatically using toolkit lookups when user asks about installment, stock, or care packages.
     *
     * @param  array<string, mixed>  $context
     * @return array{tool_calls: list<string>, results: array<string, mixed>}
     */
    public function executeGroundingForMessage(string $message, array $context = []): array
    {
        $lowered = Str::lower($message);
        $ascii = Str::ascii($lowered);
        $toolCalls = [];
        $results = [];

        $currentProductId = isset($context['current_product']['id']) && is_numeric($context['current_product']['id'])
            ? (int) $context['current_product']['id']
            : null;
        $firstVehicleId = collect($context['products'] ?? [])
            ->first(static fn (array $item): bool => ! ($item['is_accessory'] ?? false) && ! empty($item['id']))['id'] ?? null;
        $firstAccessoryId = collect($context['products'] ?? [])
            ->first(static fn (array $item): bool => ($item['is_accessory'] ?? false) === true && ! empty($item['id']))['id'] ?? null;

        if (Str::contains($lowered, ['trả góp', 'lãi suất', 'dự toán', 'vay mua', 'trả trước'])
            || Str::contains($ascii, ['tra gop', 'lai suat', 'du toan', 'vay mua', 'tra truoc'])) {
            $targetProductId = $currentProductId ?? ($firstVehicleId ? (int) $firstVehicleId : null);
            if ($targetProductId !== null) {
                $estimate = $this->calculateFinancialEstimate($targetProductId);
                if ($estimate !== null) {
                    $toolCalls[] = 'calculate_financial_estimate';
                    $results['financial_estimate'] = $estimate;
                }
            }
        }

        if (Str::contains($lowered, ['tồn kho', 'còn hàng', 'hết hàng', 'phụ kiện', 'số lượng kho'])
            || Str::contains($ascii, ['ton kho', 'con hang', 'het hang', 'phu kien', 'so luong kho'])) {
            $targetAccessoryId = (($context['current_product']['is_accessory'] ?? false) === true && $currentProductId !== null)
                ? $currentProductId
                : ($firstAccessoryId ? (int) $firstAccessoryId : null);
            if ($targetAccessoryId !== null) {
                $stockInfo = $this->checkAccessoryStock($targetAccessoryId);
                if ($stockInfo['found']) {
                    $toolCalls[] = 'check_accessory_stock';
                    $results['accessory_stock'] = $stockInfo;
                }
            }
        }

        if (Str::contains($lowered, ['bảo dưỡng', 'ceramic', 'rửa xe', 'ista', 'chăm sóc xe', 'nội thất da', 'khoang máy'])
            || Str::contains($ascii, ['bao duong', 'ceramic', 'rua xe', 'ista', 'cham soc xe', 'noi that da', 'khoang may', 'service'])) {
            $toolCalls[] = 'list_service_packages';
            $results['service_packages'] = $this->listServicePackages();
        }

        return [
            'tool_calls' => array_values(array_unique($toolCalls)),
            'results' => $results,
        ];
    }
}
