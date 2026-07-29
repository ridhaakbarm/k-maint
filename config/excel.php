<?php

return [
    'cache' => [
        'driver' => env('EXCEL_CACHE_DRIVER', 'batch'),
        'batch' => [
            'memory_limit' => env('EXCEL_CACHE_MEMORY_LIMIT', 50000),
        ],
        'illuminate' => [
            'store' => env('EXCEL_CACHE_STORE', 'excel'),
        ],
        'default_ttl' => env('EXCEL_CACHE_TTL', 10800),
    ],
];
