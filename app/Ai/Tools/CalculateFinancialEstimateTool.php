<?php

namespace App\Ai\Tools;

use App\Services\Ai\ShowroomAiToolkit;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CalculateFinancialEstimateTool implements Tool
{
    public function __construct(
        private readonly ShowroomAiToolkit $toolkit,
    ) {}

    public function name(): string
    {
        return 'calculate_financial_estimate';
    }

    public function description(): Stringable|string
    {
        return 'Tính toán bảng dự toán trả góp chính xác từ giá niêm yết thực tế trong DB cho mẫu xe BMW theo ID sản phẩm, tỷ lệ trả trước (%) và kỳ hạn vay (tháng).';
    }

    public function handle(Request $request): Stringable|string
    {
        $productId = (int) ($request['product_id'] ?? 0);
        $downPaymentPercent = isset($request['down_payment_percent']) && is_numeric($request['down_payment_percent'])
            ? (int) $request['down_payment_percent']
            : 20;
        $months = isset($request['months']) && is_numeric($request['months'])
            ? (int) $request['months']
            : 60;

        $estimate = $this->toolkit->calculateFinancialEstimate($productId, $downPaymentPercent, $months);

        if ($estimate === null) {
            return json_encode([
                'status' => 'not_found',
                'message' => 'Không tìm thấy sản phẩm hợp lệ hoặc sản phẩm chưa có giá niêm yết.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        return json_encode([
            'status' => 'ok',
            'estimate' => $estimate,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()
                ->description('ID sản phẩm BMW cần tính dự toán trả góp.')
                ->required(),
            'down_payment_percent' => $schema->integer()
                ->description('Tỷ lệ trả trước (% từ 10 đến 80, mặc định 20).')
                ->nullable(),
            'months' => $schema->integer()
                ->description('Kỳ hạn vay (tháng từ 12 đến 84, mặc định 60).')
                ->nullable(),
        ];
    }
}
