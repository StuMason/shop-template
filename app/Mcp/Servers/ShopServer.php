<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddToBasket;
use App\Mcp\Tools\CreateBasket;
use App\Mcp\Tools\GetOrderStatus;
use App\Mcp\Tools\GetProduct;
use App\Mcp\Tools\ListShippingMethods;
use App\Mcp\Tools\RemoveFromBasket;
use App\Mcp\Tools\SearchProducts;
use App\Mcp\Tools\StartCheckout;
use App\Mcp\Tools\UpdateBasketItem;
use App\Mcp\Tools\ViewBasket;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Shop')]
#[Version('1.0.0')]
#[Instructions(<<<'MD'
This server lets you browse this shop's catalogue and assemble a purchase on
behalf of a customer.

Typical flow: search-products -> get-product -> create-basket -> add-to-basket
-> list-shipping-methods -> start-checkout.

Payment is pay-by-bank (open banking). You can do everything up to the payment
itself; start-checkout returns a secure pay_url that the HUMAN customer must
open to review the order and authorise the payment in their own banking app.
Never claim to have paid for an order. Keep the basket_token private.
MD)]
class ShopServer extends Server
{
    protected array $tools = [
        SearchProducts::class,
        GetProduct::class,
        CreateBasket::class,
        AddToBasket::class,
        ViewBasket::class,
        UpdateBasketItem::class,
        RemoveFromBasket::class,
        ListShippingMethods::class,
        StartCheckout::class,
        GetOrderStatus::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
