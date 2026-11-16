<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetVehicleDetailsMcpTool;
use App\Mcp\Tools\SearchShowroomKnowledgeMcpTool;
use App\Mcp\Tools\SearchVehiclesMcpTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

#[Name('BMW Showroom MCP Server')]
#[Version('1.0.0')]
#[Instructions('Cổng kết nối chuẩn Model Context Protocol (MCP) của BMW Showroom Vietnam. Dùng các công cụ read-only để tra cứu danh mục xe BMW, thông số kỹ thuật tiếng Việt, phụ kiện chính hãng và chính sách showroom được xác thực.')]
class BmwShowroomServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        SearchVehiclesMcpTool::class,
        GetVehicleDetailsMcpTool::class,
        SearchShowroomKnowledgeMcpTool::class,
    ];
}
