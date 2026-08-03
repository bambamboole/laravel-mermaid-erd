<?php

declare(strict_types=1);

namespace Bambamboole\LaravelMermaidErd\Http;

use Bambamboole\LaravelMermaidErd\MermaidErdRenderer;
use Bambamboole\LaravelMermaidErd\Schema\SchemaBuilder;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class MermaidErdController
{
    public function __invoke(Request $request, SchemaBuilder $builder): ViewContract|Response
    {
        $connectionName = config('database.default');
        $cacheKey = "mermaid-erd:view:{$connectionName}";

        $generate = function () use ($builder): array {
            $schema = $builder->build();

            return [
                'diagram' => (new MermaidErdRenderer)->render($schema),
                'graph' => $schema->toGraph(),
            ];
        };

        $data = config('mermaid-erd.web.cache.enabled')
            ? Cache::remember($cacheKey, config('mermaid-erd.web.cache.ttl', 3600), $generate)
            : $generate();

        if ($request->boolean('raw')) {
            return new Response($data['diagram'], 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $jsonFlags = JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

        return View::make('mermaid-erd::diagram', [
            'connectionName' => $connectionName,
            'diagram' => json_encode($data['diagram'], $jsonFlags),
            'graph' => json_encode($data['graph'], $jsonFlags),
            'mermaidConfig' => json_encode(config('mermaid-erd.web.mermaid', []), $jsonFlags),
        ]);
    }
}
