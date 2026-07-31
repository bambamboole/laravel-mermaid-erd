<?php declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

it('returns 200', function (): void {
    $this->get('/mermaid-erd')->assertOk();
});

it('uses the diagram blade view', function (): void {
    $response = $this->get('/mermaid-erd');

    $response->assertOk();
    $response->assertViewIs('mermaid-erd::diagram');
    $response->assertViewHas('connectionName');
    $response->assertViewHas('diagram');
    $response->assertViewHas('graph');
    $response->assertViewHas('mermaidConfig');
});

it('contains required html structure', function (): void {
    $response = $this->get('/mermaid-erd');

    $response->assertOk();
    $response->assertSee('<!DOCTYPE html>', false);
    $response->assertSee('mermaid.min.js', false);
    $response->assertSee('id="diagram-wrapper"', false);
    $response->assertSee('tailwindcss', false);
});

it('contains the filter search box and graph payload', function (): void {
    $response = $this->get('/mermaid-erd');

    $response->assertOk();
    $response->assertSee('id="erd-search"', false);
    $response->assertSee('const graph =', false);
    $response->assertSee('"edges":', false);
});

it('contains diagram with table names', function (): void {
    $response = $this->get('/mermaid-erd');

    $response->assertOk();
    $response->assertSee('erDiagram', false);
    $response->assertSee('users', false);
    $response->assertSee('posts', false);
    $response->assertSee('comments', false);
    $response->assertSee('tags', false);
});

it('contains mermaid config from configuration', function (): void {
    $response = $this->get('/mermaid-erd');

    $response->assertOk();
    $response->assertSee('mermaid.initialize(', false);
    $response->assertSee('"theme":', false);
    $response->assertSee('"securityLevel":', false);
});

it('contains zoom controls', function (): void {
    $response = $this->get('/mermaid-erd');

    $response->assertOk();
    $response->assertSee('id="zoom-level"', false);
    $response->assertSee('zoomIn()', false);
    $response->assertSee('zoomOut()', false);
    $response->assertSee('resetView()', false);
});

it('does not cache when cache is disabled', function (): void {
    config()->set('mermaid-erd.web.cache.enabled', false);

    $this->get('/mermaid-erd')->assertOk();

    $connectionName = config('database.default');
    expect(Cache::has("mermaid-erd:view:{$connectionName}"))->toBeFalse();
});

it('caches diagram when cache is enabled', function (): void {
    config()->set('mermaid-erd.web.cache.enabled', true);
    config()->set('mermaid-erd.web.cache.ttl', 3600);

    $this->get('/mermaid-erd')->assertOk();

    $connectionName = config('database.default');
    expect(Cache::has("mermaid-erd:view:{$connectionName}"))->toBeTrue();
});

it('has named route mermaid-erd', function (): void {
    expect(route('mermaid-erd'))->toEndWith('/mermaid-erd');
});

it('contains copy and download buttons', function (): void {
    $response = $this->get('/mermaid-erd');

    $response->assertOk();
    $response->assertSee('copyMermaid()', false);
    $response->assertSee('downloadSVG()', false);
});
