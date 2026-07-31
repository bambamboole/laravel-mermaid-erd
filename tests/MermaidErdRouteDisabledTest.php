<?php declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Tests;

class MermaidErdRouteDisabledTest extends TestCase
{
    #[\Override]
    public function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('mermaid-erd.web.enabled', false);
    }

    public function test_returns_404_when_route_is_disabled(): void
    {
        $this->get('/mermaid-erd')->assertNotFound();
    }
}
