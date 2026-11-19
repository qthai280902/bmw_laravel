<?php

namespace App\Ai\Tools;

use App\Services\Ai\ShowroomAiToolkit;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListServicePackagesTool implements Tool
{
    public function __construct(
        private readonly ShowroomAiToolkit $toolkit,
    ) {}

    public function name(): string
    {
        return 'list_service_packages';
    }

    public function description(): Stringable|string
    {
        return 'Liệt kê đầy đủ 4 gói dịch vụ chăm sóc xe & bảo dưỡng chính hãng BMW kèm thời gian dự kiến, chi phí tham khảo và đường dẫn đặt lịch điền sẵn.';
    }

    public function handle(Request $request): Stringable|string
    {
        return json_encode([
            'status' => 'ok',
            'packages' => $this->toolkit->listServicePackages(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
