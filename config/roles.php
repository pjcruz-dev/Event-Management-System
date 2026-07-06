<?php

declare(strict_types=1);

return [
    'owner' => '*',

    'admin' => [
        'events.create',
        'events.view',
        'events.manage',
        'events.publish',
        'members.invite',
        'members.manage',
        'settings.manage',
        'orders.view',
        'orders.refund',
        'checkin.scan',
        'exhibitors.manage',
        'reports.export',
    ],

    'member' => [
        'events.view',
        'orders.view',
        'checkin.scan',
    ],
];
