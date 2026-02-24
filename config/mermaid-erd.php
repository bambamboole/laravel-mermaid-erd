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

    'guess_relationships' => true,

    'polymorphic_relationships' => [
        // 'table.morph_name' => [target_tables]
        // 'comments.commentable' => ['posts', 'videos'],
    ],

    'web' => [
        'enabled' => true,
        'route' => '/mermaid-erd',
        'middleware' => ['web'],

        'cache' => [
            'enabled' => false,
            'ttl' => 3600,
        ],

        'mermaid' => [
            'theme' => 'default',
            'securityLevel' => 'loose',
            'logLevel' => 'error',
            'er' => [
                'useMaxWidth' => false,
            ],
        ],
    ],
];
