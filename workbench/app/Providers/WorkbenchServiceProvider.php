<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

use function Orchestra\Testbench\package_path;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::view('/', 'welcome');

        config(['mermaid-erd.models.paths' => [package_path('workbench/app/Models')]]);
    }
}
