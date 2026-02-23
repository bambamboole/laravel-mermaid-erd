<?php declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Tests;

class MermaidErdRouteCustomPathTest extends TestCase
{
    public function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('mermaid-erd.web.route', '/custom-erd');
    }

    public function test_returns_200_at_custom_path(): void
    {
        $this->get('/custom-erd')->assertOk();
    }

    public function test_returns_404_at_default_path(): void
    {
        $this->get('/mermaid-erd')->assertNotFound();
    }
}
