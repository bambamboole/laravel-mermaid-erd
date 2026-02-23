<?php

declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Http;

use Bambamboole\LaravelMermaidErd\MermaidErdGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class MermaidErdController
{
    public function __invoke(MermaidErdGenerator $generator): View
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

        return view('mermaid-erd::diagram', [
            'connectionName' => $connectionName,
            'diagram' => htmlspecialchars($diagram, ENT_QUOTES, 'UTF-8'),
            'mermaidConfig' => json_encode(config('mermaid-erd.web.mermaid', []), JSON_THROW_ON_ERROR),
        ]);
    }
}
