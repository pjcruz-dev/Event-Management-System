<?php

declare(strict_types=1);

return [
    'signing_key' => env('CHECKIN_SIGNING_KEY', env('APP_KEY')),
];
