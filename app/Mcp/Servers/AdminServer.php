<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AdminGetOrder;
use App\Mcp\Tools\AdminListOrders;
use App\Mcp\Tools\AdminLowStock;
use App\Mcp\Tools\AdminOpenTickets;
use App\Mcp\Tools\AdminSalesSummary;
use App\Mcp\Tools\AdminSearchCustomers;
use App\Mcp\Tools\AdminShipOrder;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Shop Admin')]
#[Version('1.0.0')]
#[Instructions(<<<'MD'
Private administration server for shop staff, authenticated via OAuth.

Reporting: admin-sales-summary (revenue, AOV, top products), admin-low-stock,
admin-search-customers. Orders: admin-list-orders, admin-get-order, and
admin-ship-order (emails the customer, include tracking when you have it).
Support: admin-open-tickets shows the queue.

Money values are formatted strings in the shop currency. Be careful with
admin-ship-order — it is the only mutating tool here.
MD)]
class AdminServer extends Server
{
    protected array $tools = [
        AdminSalesSummary::class,
        AdminListOrders::class,
        AdminGetOrder::class,
        AdminShipOrder::class,
        AdminLowStock::class,
        AdminSearchCustomers::class,
        AdminOpenTickets::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
