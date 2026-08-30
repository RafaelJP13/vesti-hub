<?php

return [
    'default' => env('DB_CONNECTION'),
    'connections' => [],
    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],
    'redis' => [],
];
