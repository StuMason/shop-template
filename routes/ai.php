<?php

use App\Http\Middleware\EnsureStaff;
use App\Mcp\Servers\AdminServer;
use App\Mcp\Servers\ShopServer;
use Laravel\Mcp\Facades\Mcp;

// OAuth discovery + dynamic client registration for MCP clients (Passport
// is the authorization server).
Mcp::oauthRoutes();

// Public storefront server: agents browse, build baskets, get pay links.
Mcp::web('/mcp/shop', ShopServer::class)
    ->middleware('throttle:mcp')
    ->name('mcp.shop');

// Private admin server: staff connect their MCP client via OAuth (log in and
// approve in the browser), then administer the shop conversationally.
Mcp::web('/mcp/admin', AdminServer::class)
    ->middleware(['auth:api', EnsureStaff::class])
    ->name('mcp.admin');
