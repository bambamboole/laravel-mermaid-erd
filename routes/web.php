<?php

declare(strict_types=1);

use Bambamboole\LaravelMermaidErd\Http\MermaidErdController;
use Illuminate\Support\Facades\Route;

if (! config('mermaid-erd.web.enabled')) {
    return;
}
Route::middleware(config('mermaid-erd.web.middleware', ['web']))
    ->get(config('mermaid-erd.web.route', '/mermaid-erd'), MermaidErdController::class)
    ->name('mermaid-erd');
