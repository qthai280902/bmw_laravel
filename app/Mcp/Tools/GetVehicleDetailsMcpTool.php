<?php

namespace App\Mcp\Tools;

use App\Enums\VehicleType;
use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lấy thông tin chi tiết, giá niêm yết, tiền đặt cọc, thông số kỹ thuật tiếng Việt và đường dẫn đặt lịch của một mẫu xe hoặc phụ kiện BMW theo slug.')]
class GetVehicleDetailsMcpTool extends Tool
{
    public function handle(Request $request): Response
    {
        $slug = trim((string) ($request->get('slug') ?? ''));

        if ($slug === '') {
            return Response::error('Vui lòng cung cấp slug sản phẩm cần tra cứu.');
        }

        $product = Product::query()
            ->active()
            ->with(['category'])
            ->where('slug', $slug)
            ->first();

        if (! $product) {
            return Response::error("Không tìm thấy sản phẩm đang mở bán với slug '{$slug}'.");
        }

        $payload = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'type' => $product->type instanceof VehicleType ? $product->type->label() : (string) $product->type,
            'category' => $product->category?->name,
            'price_vnd' => (int) $product->price,
            'deposit_vnd' => $product->deposit_amount ? (int) $product->deposit_amount : null,
            'available' => (bool) $product->is_active,
            'description' => $product->description,
            'translated_specs' => $product->translated_specs,
            'urls' => [
                'detail' => route('products.show', $product->slug),
                'action' => $product->isAccessory()
                    ? route('accessory-orders.create', $product->slug)
                    : route('appointments.create', ['type' => 'test_drive', 'product_id' => $product->id]),
            ],
        ];

        return Response::text(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()
                ->description('Slug của mẫu xe hoặc phụ kiện (ví dụ: bmw-330i-sedan).')
                ->required(),
        ];
    }
}
