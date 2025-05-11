<?php 

return [
    'prices' => [
        'basic_plan' => env('STRIPE_BASIC_PLAN_PRICE_ID'),
        'pro_plan'   => env('STRIPE_PRO_PLAN_PRICE_ID', 'your_pro_plan_price_id_here'),
        // Add other price IDs mapped to meaningful names
        // 'premium_plan' => env('STRIPE_PREMIUM_PLAN_PRICE_ID'),
    ],
    // You could also store product IDs if needed
    // 'products' => [
    //     'basic_product' => env('STRIPE_BASIC_PRODUCT_ID'),
    // ]
];