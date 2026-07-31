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

    'schema' => null,

    'guess_relationships' => true,

    'models' => [
        // Scan Eloquent models to annotate tables (model class), columns
        // (casts, accessors, mutators) and auto-discover polymorphic relations
        // from typed morphOne/morphMany/morphToMany methods.
        'enabled' => true,

        // Directories to scan. null defaults to [app_path('Models')].
        'paths' => null,
    ],

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
