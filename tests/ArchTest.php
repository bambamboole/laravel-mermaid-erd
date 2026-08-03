<?php

declare(strict_types=1);

arch('no debug statements ship in the package')
    ->expect(['dd', 'ddd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('the package uses strict types throughout')
    ->expect('Bambamboole\LaravelMermaidErd')
    ->toUseStrictTypes();
