<?php

use App\Mcp\Servers\ShopServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/shop', ShopServer::class)
    ->middleware('throttle:mcp')
    ->name('mcp.shop');
