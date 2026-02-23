<?php
declare(strict_types=1);

use Bambamboole\LaravelMermaidErd\Http\MermaidErdController;
use Illuminate\Support\Facades\Route;

if (!config('mermaid-erd.route.enabled')) {
    return;
}
Route::middleware(config('mermaid-erd.route.middleware', ['web']))
    ->get(config('mermaid-erd.route.path', '/mermaid-erd'), MermaidErdController::class)
    ->name('mermaid-erd');
