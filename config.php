
<?php
// FoodHub Database Configuration

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 4306,
        'name' => 'foodhub_db',
        'user' => 'root',
        'pass' => '',
    ],

    'app' => [
        'name' => 'FoodHub',
        'session_timeout' => 1800,
        'delivery_fee' => 60,
        'free_delivery_over' => 500,
        'debug' => true,
    ],
];
