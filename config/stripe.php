<?php 

return [
    'prices' => [
        'basic_plan' => env('STRIPE_BASIC_PLAN_PRICE_ID'),
        // Add other price IDs mapped to meaningful names
        // 'premium_plan' => env('STRIPE_PREMIUM_PLAN_PRICE_ID'),
    ],
    // You could also store product IDs if needed
    // 'products' => [
    //     'basic_product' => env('STRIPE_BASIC_PRODUCT_ID'),
    // ]
];