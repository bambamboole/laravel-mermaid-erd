<?php declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

it('returns 200 with html content type', function () {
    $response = $this->get('/mermaid-erd');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
});

it('contains required html structure', function () {
    $response = $this->get('/mermaid-erd');

    $response->assertOk();
    $response->assertSee('<!DOCTYPE html>', false);
    $response->assertSee('mermaid.min.js', false);
    $response->assertSee('<pre class="mermaid">', false);
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

it('does not cache when cache is disabled', function () {
    config()->set('mermaid-erd.cache.enabled', false);

    $this->get('/mermaid-erd')->assertOk();

    $connectionName = config('database.default');
    expect(Cache::has("mermaid-erd:diagram:{$connectionName}"))->toBeFalse();
});

it('caches diagram when cache is enabled', function () {
    config()->set('mermaid-erd.cache.enabled', true);
    config()->set('mermaid-erd.cache.ttl', 3600);

    $this->get('/mermaid-erd')->assertOk();

    $connectionName = config('database.default');
    expect(Cache::has("mermaid-erd:diagram:{$connectionName}"))->toBeTrue();
});

it('has named route mermaid-erd', function () {
    expect(route('mermaid-erd'))->toEndWith('/mermaid-erd');
});
