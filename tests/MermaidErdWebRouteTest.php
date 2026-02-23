<?php declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

it('returns 200', function () {
    $this->get('/mermaid-erd')->assertOk();
});

it('uses the diagram blade view', function () {
    $response = $this->get('/mermaid-erd');

    $response->assertOk();
    $response->assertViewIs('mermaid-erd::diagram');
    $response->assertViewHas('connectionName');
    $response->assertViewHas('diagram');
    $response->assertViewHas('mermaidConfig');
});

it('contains required html structure', function () {
    $response = $this->get('/mermaid-erd');

    $response->assertOk();
    $response->assertSee('<!DOCTYPE html>', false);
    $response->assertSee('mermaid.min.js', false);
    $response->assertSee('<pre class="mermaid', false);
    $response->assertSee('tailwindcss', false);
});

it('contains diagram with table names', function () {
    $response = $this->get('/mermaid-erd');

    $response->assertOk();
    $response->assertSee('erDiagram', false);
    $response->assertSee('users', false);
    $response->assertSee('posts', false);
    $response->assertSee('comments', false);
    $response->assertSee('tags', false);
});

it('contains mermaid config from configuration', function () {
    $response = $this->get('/mermaid-erd');

    $response->assertOk();
    $response->assertSee('mermaid.initialize(', false);
    $response->assertSee('"theme":', false);
    $response->assertSee('"securityLevel":', false);
});

it('contains zoom controls', function () {
    $response = $this->get('/mermaid-erd');

    $response->assertOk();
    $response->assertSee('id="zoom-level"', false);
    $response->assertSee('zoomIn()', false);
    $response->assertSee('zoomOut()', false);
    $response->assertSee('resetView()', false);
});

it('does not cache when cache is disabled', function () {
    config()->set('mermaid-erd.web.cache.enabled', false);

    $this->get('/mermaid-erd')->assertOk();

    $connectionName = config('database.default');
    expect(Cache::has("mermaid-erd:diagram:{$connectionName}"))->toBeFalse();
});

it('caches diagram when cache is enabled', function () {
    config()->set('mermaid-erd.web.cache.enabled', true);
    config()->set('mermaid-erd.web.cache.ttl', 3600);

    $this->get('/mermaid-erd')->assertOk();

    $connectionName = config('database.default');
    expect(Cache::has("mermaid-erd:diagram:{$connectionName}"))->toBeTrue();
});

it('has named route mermaid-erd', function () {
    expect(route('mermaid-erd'))->toEndWith('/mermaid-erd');
});
