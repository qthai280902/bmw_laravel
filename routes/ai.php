<?php

use App\Mcp\Servers\BmwShowroomServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/showroom', BmwShowroomServer::class);
Mcp::local('bmw-showroom', BmwShowroomServer::class);
