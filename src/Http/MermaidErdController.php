<?php

declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Http;

use Bambamboole\LaravelMermaidErd\MermaidErdGenerator;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class MermaidErdController
{
    public function __invoke(MermaidErdGenerator $generator): ViewContract
    {
        $connectionName = config('database.default');
        $cacheKey = "mermaid-erd:diagram:{$connectionName}";

        if (config('mermaid-erd.web.cache.enabled')) {
            $diagram = Cache::remember(
                $cacheKey,
                config('mermaid-erd.web.cache.ttl', 3600),
                fn () => $generator->generate(),
            );
        } else {
            $diagram = $generator->generate();
        }

        return View::make('mermaid-erd::diagram', [
            'connectionName' => $connectionName,
            'diagram' => htmlspecialchars($diagram, ENT_QUOTES, 'UTF-8'),
            'mermaidConfig' => json_encode(config('mermaid-erd.web.mermaid', []), JSON_THROW_ON_ERROR),
        ]);
    }
}
