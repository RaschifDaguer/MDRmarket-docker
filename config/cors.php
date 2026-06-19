<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['POST', 'GET', 'OPTIONS', 'DELETE', 'PUT', 'PATCH'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Auth-Token', 'Origin', 'Authorization', 'Accept', 'X-Requested-With', 'ngrok-skip-browser-warning'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
