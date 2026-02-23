<?php declare(strict_types=1);

return [
    'ignore_tables' => [
        'migrations',
        'failed_jobs',
        'sessions',
        'personal_access_tokens',
        'password_reset_tokens',
        'jobs',
        'job_batches',
        'cache',
        'cache_locks',
    ],

    'route' => [
        'enabled' => true,
        'path' => '/mermaid-erd',
        'middleware' => ['web'],
    ],

    'cache' => [
        'enabled' => false,
        'ttl' => 3600,
    ],
];
