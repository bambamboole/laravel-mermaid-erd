<?php declare(strict_types=1);

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;
use Bambamboole\LaravelMermaidErd\MermaidErdRenderer;
use Bambamboole\LaravelMermaidErd\Schema\ModelScan;
use Bambamboole\LaravelMermaidErd\Schema\ModelScanner;
use Bambamboole\LaravelMermaidErd\Schema\RelationType;
use Bambamboole\LaravelMermaidErd\Schema\Schema;
use Bambamboole\LaravelMermaidErd\Schema\SchemaBuilder;
use Workbench\App\Enums\OrderStatus;
use Workbench\App\Models\Order;
use Workbench\App\Models\User;

function scanWorkbenchModels(): ModelScan
{
    return (new ModelScanner([__DIR__.'/../workbench/app/Models']))->scan();
}

function buildEnrichedSchema(array $polymorphicRelationships = [], ?ModelScan $scan = null): Schema
{
    return (new SchemaBuilder(
        new DatabaseInformationService(app('db')->connection()),
        $polymorphicRelationships,
        true,
        $scan ?? scanWorkbenchModels(),
    ))->build();
}

it('discovers models and keys them by table', function (): void {
    $scan = scanWorkbenchModels();

    expect($scan->models['orders']->class)->toBe(Order::class)
        ->and($scan->models['users']->class)->toBe(User::class)
        ->and($scan->models)->not->toHaveKey('post_tag');
});

it('collects declared casts without the implicit key cast', function (): void {
    $casts = scanWorkbenchModels()->models['orders']->casts;

    expect($casts['status'])->toBe(OrderStatus::class)
        ->and($casts['placed_at'])->toBe('immutable_datetime')
        ->and($casts)->not->toHaveKey('id');
});

it('detects modern and legacy accessors', function (): void {
    $scan = scanWorkbenchModels();

    expect($scan->models['customers']->accessors)->toContain('name')
        ->and($scan->models['customers']->mutators)->not->toContain('name')
        ->and($scan->models['videos']->accessors)->toContain('display_title');
});

it('discovers morph relations from typed relation methods', function (): void {
    $morphs = scanWorkbenchModels()->morphRelationships;

    expect($morphs['reviews.reviewable'])->toBe(['posts', 'videos'])
        ->and($morphs['attachments.attachable'])->toBe(['posts', 'videos']);
});

it('returns an empty scan for nonexistent paths', function (): void {
    $scan = (new ModelScanner(['/nonexistent/path']))->scan();

    expect($scan->models)->toBe([])
        ->and($scan->morphRelationships)->toBe([]);
});

it('annotates tables and columns with model metadata', function (): void {
    $schema = buildEnrichedSchema();

    $orders = $schema->table('orders');
    expect($orders->model)->toBe(Order::class);

    $status = collect($orders->columns)->firstWhere('name', 'status');
    expect($status->cast)->toBe(OrderStatus::class);

    $name = collect($schema->table('customers')->columns)->firstWhere('name', 'name');
    expect($name->accessor)->toBeTrue()
        ->and($name->mutator)->toBeFalse();
});

it('maps scanned morphs so nothing is left unmapped', function (): void {
    expect(buildEnrichedSchema()->unmappedMorphs())->toBe([]);
});

it('merges configured morph targets with scanned ones', function (): void {
    $schema = buildEnrichedSchema(['reviews.reviewable' => ['tags']]);

    $targets = collect($schema->relations)
        ->filter(fn ($relation): bool => $relation->type === RelationType::Morph && $relation->to === 'reviews')
        ->pluck('from');

    expect($targets->all())->toBe(['posts', 'videos', 'tags']);
});

it('skips morph relations pointing outside the schema', function (): void {
    $schema = buildEnrichedSchema(['reviews.reviewable' => ['nonexistent_table']]);

    expect(collect($schema->relations)->contains(fn ($relation): bool => $relation->from === 'nonexistent_table'))->toBeFalse();
});

it('renders model metadata into the diagram', function (): void {
    $diagram = (new MermaidErdRenderer)->render(buildEnrichedSchema());

    expect($diagram)
        ->toContain('orders["orders (9) · Order"]')
        ->toContain('cast: OrderStatus')
        ->toContain('posts ||--o{ reviews : "morphMany via reviewable"')
        ->toContain('videos ||--o{ attachments : "morphMany via attachable"')
        ->toMatch('/customers\[.*?\] \{[^}]*\w+ name "accessor"/s');
});

it('includes model names in the graph payload', function (): void {
    $graph = buildEnrichedSchema()->toGraph();

    expect($graph['models']['orders'])->toBe('Order')
        ->and($graph['models'])->not->toHaveKey('post_tag');
});
