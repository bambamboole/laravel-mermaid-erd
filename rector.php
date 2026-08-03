<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/src', __DIR__.'/tests', __DIR__.'/workbench/app'])
    ->withPhpSets()
    ->withPreparedSets(deadCode: true, codeQuality: true, typeDeclarations: true)
    ->withSkip([
        // Untyped Pest closures are the house style in tests/; typing every test callback
        // is churn that adds no safety. Scoped to tests/ so src/ keeps the rule.
        AddClosureVoidReturnTypeWhereNoReturnRector::class => [__DIR__.'/tests'],
        // Laravel resolves many signatures by reflection — policy methods, middleware handle(),
        // listeners, authorize(). Stripping a parameter the body ignores breaks them at runtime.
        RemoveUnusedPublicMethodParameterRector::class,
    ]);
