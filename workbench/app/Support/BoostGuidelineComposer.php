<?php
declare(strict_types=1);
namespace Workbench\App\Support;

use Laravel\Boost\Install\GuidelineComposer;

use function Orchestra\Testbench\package_path;

/**
 * Resolve custom guidelines (.ai/guidelines) from the package root instead of
 * the Testbench skeleton.
 */
class BoostGuidelineComposer extends GuidelineComposer
{
    #[\Override]
    public function customGuidelinePath(string $path = ''): string
    {
        return package_path($this->userGuidelineDir.'/'.ltrim($path, '/'));
    }
}
