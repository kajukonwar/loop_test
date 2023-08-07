<?php 
 
return [
    'data_source' => [
        'url' => [
            'customers' => env('CUSTOMERS_SOURCE_URL', ''),
            'products' => env('PRODUCTS_SOURCE_URL', ''),
        ],
        'auth' => [
            'username' => env('LOOP_USERNAME', ''),
            'password' => env('LOOP_PASSWORD', '')
        ]
    ],
    'storage' => [
        'path' => [
            'customers' => env('CUSTOMERS_STORAGE_PATH', ''),
            'products' => env('PRODUCTS_STORAGE_PATH', '')
        ]
    ],
    'payment' => [
        'providers' => [
            'super_payment_provider' => [
                'url' => env('SUPER_PAYMENT_URL', '')
            ]
            ],
        'default_provider' => 'super_payment_provider'
    ]
];