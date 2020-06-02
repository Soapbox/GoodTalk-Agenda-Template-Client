<?php

return [
    'http' => [
        'connect_timeout' => env('REMOTE_CLIENT_HTTP_CONNECT_TIMEOUT', 15),
        'timeout' => env('REMOTE_CLIENT_HTTP_TIMEOUT', 15),
    ],
    'base_url' => env('AGENDA_TEMPLATE_API_URL'),
    'signed-requests' => [
        'algorithm' => env('ATC_SIGNED_REQUEST_ALGORITHM', 'sha256'),
        'cache-prefix' => env('ATC_SIGNED_REQUEST_CACHE_PREFIX', 'signed-requests'),
    ],
    'headers' => [
        'signature' => env('ATC_SIGNED_REQUEST_SIGNATURE_HEADER', 'X-Signature'),
        'algorithm' => env('ATC_SIGNED_REQUEST_ALGORITHM_HEADER', 'X-Signature-Algorithm'),
    ],
    'key' => env('ATC_SIGNED_REQUEST_KEY', 'customKey'),
    'request-replay' => [
        'allow' => env('ATC_SIGNED_REQUEST_ALLOW_REPLAYS', false),
        'tolerance' => env('ATC_SIGNED_REQUEST_TOLERANCE_SECONDS', 30),
    ],
];
