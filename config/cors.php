<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST'],
    'allowed_origins' => ['https://delightre.com', 'http://localhost:3000', 'http://localhost:3100'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => false,
];
