<?php

namespace App\Ai\Tools;

use App\Services\Ai\ShowroomAiToolkit;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CheckAccessoryStockTool implements Tool
{
    public function __construct(
        private readonly ShowroomAiToolkit $toolkit,
    ) {}

    public function name(): string
    {
        return 'check_accessory_stock';
    }

    public function description(): Stringable|string
    {
        return 'Kiểm tra trạng thái còn hàng, số lượng tồn kho thực tế trong DB và liên kết đặt hàng cho phụ kiện chính hãng BMW theo ID.';
    }

    public function handle(Request $request): Stringable|string
    {
        $productId = (int) ($request['product_id'] ?? 0);
        $stockInfo = $this->toolkit->checkAccessoryStock($productId);

        return json_encode($stockInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()
                ->description('ID phụ kiện BMW cần kiểm tra tồn kho.')
                ->required(),
        ];
    }
}
