<?php

return [
    'connections' => [
        'rethinkdb' => [
            'name'	   => 'rethinkdb',
            'driver'   => 'rethinkdb',
            'host'     => env('DB_HOST', 'localhost'),
            'port'     => env('DB_PORT', 28015),
            'database' => env('DB_DATABASE', 'unittest'),
        ],
    ]
];
