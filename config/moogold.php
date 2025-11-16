<?php
return [

    'secret_key' => 'I75KsLRAf8', // Merchant UID
    'email' => 'yeminthandev@gmail.com', // Merchant Email
    'api_key' => '6e0b6fb27bc23f9e504baa7a2e692b79', // Merchant Key
    'domain' => 'https://moogold.com', //Domain URL

    "API_URL" => [
        "list-product" => "/wp-json/v1/api/product/list_product",
        "product-detail" => "/wp-json/v1/api/product/product_detail",

        "create-order" => "/wp-json/v1/api/order/create_order",
        "order-detail" => "/wp-json/v1/api/order/order_detail",

        "game-server_list" => "/wp-json/v1/api/product/server_list",
        "account-validate" => "/wp-json/v1/api/product/validate",

        "balance" => "/wp-json/v1/api/user/balance",
        "recharge-balance" => "/wp-json/v1/api/user/reload_balance",
    ]

];