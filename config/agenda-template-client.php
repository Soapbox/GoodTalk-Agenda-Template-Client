<?php

return [
    'http' => [
        'connect_timeout' => env('REMOTE_CLIENT_HTTP_CONNECT_TIMEOUT', 15),
        'timeout' => env('REMOTE_CLIENT_HTTP_TIMEOUT', 15),
    ],
    'base_url' => env('AGENDA_TEMPLATE_API_URL'),
];
