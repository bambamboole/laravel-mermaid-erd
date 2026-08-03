<?php

declare(strict_types=1);

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;
use Bambamboole\LaravelMermaidErd\MermaidErdRenderer;
use Bambamboole\LaravelMermaidErd\Schema\ModelScan;
use Bambamboole\LaravelMermaidErd\Schema\RelationType;
use Bambamboole\LaravelMermaidErd\Schema\ScannedRelation;
use Bambamboole\LaravelMermaidErd\Schema\Schema;
use Bambamboole\LaravelMermaidErd\Schema\SchemaBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as SchemaFacade;

/** @param  array<string, string[]>  $polymorphicRelationships */
function buildSchema(array $polymorphicRelationships = [], bool $guessRelationships = true): Schema
{
    return new SchemaBuilder(
        new DatabaseInformationService(app('db')->connection()),
        $polymorphicRelationships,
        $guessRelationships,
    )->build();
}

it('builds tables with column facts', function (): void {
    $users = buildSchema()->table('users');

    expect($users)->not->toBeNull();

    $columns = collect($users->columns)->keyBy('name');

    expect($columns->keys()->all())->toBe(['id', 'name', 'email', 'created_at', 'updated_at'])
        ->and($columns['id']->primaryKey)->toBeTrue()
        ->and($columns['email']->unique)->toBeTrue()
        ->and($columns['email']->primaryKey)->toBeFalse()
        ->and($columns['created_at']->nullable)->toBeTrue();
});

it('marks pivot tables', function (): void {
    $schema = buildSchema();

    expect($schema->table('post_tag')->pivot)->toBeTrue()
        ->and($schema->table('posts')->pivot)->toBeFalse();
});

it('detects morph pairs and flags their columns', function (): void {
    $reviews = buildSchema()->table('reviews');

    $columns = collect($reviews->columns)->keyBy('name');

    expect($reviews->morphNames)->toBe(['reviewable'])
        ->and($columns['reviewable_type']->polymorphic)->toBeTrue()
        ->and($columns['reviewable_id']->polymorphic)->toBeTrue();
});

it('flags soft-delete columns', function (): void {
    $columns = collect(buildSchema()->table('videos')->columns)->keyBy('name');

    expect($columns['deleted_at']->softDelete)->toBeTrue()
        ->and($columns['deleted_at']->nullable)->toBeTrue();
});

it('builds foreign key relations with normalized on-delete actions', function (): void {
    $schema = buildSchema();

    $relation = collect($schema->relations)->first(
        fn ($r): bool => $r->to === 'comments' && $r->columns === ['post_id'],
    );

    expect($relation)->not->toBeNull()
        ->and($relation->from)->toBe('posts')
        ->and($relation->type)->toBe(RelationType::ForeignKey)
        ->and($relation->onDelete)->toBe('cascade')
        ->and($relation->nullable)->toBeFalse()
        ->and($relation->oneToOne)->toBeFalse();
});

it('builds nullable self-referential relations', function (): void {
    $relation = collect(buildSchema()->relations)->first(
        fn ($r): bool => $r->to === 'categories',
    );

    expect($relation->selfReferential())->toBeTrue()
        ->and($relation->nullable)->toBeTrue()
        ->and($relation->onDelete)->toBe('set null');
});

it('guesses relations for _id columns without constraints', function (): void {
    $relation = collect(buildSchema()->relations)->first(
        fn ($r): bool => $r->to === 'ai_messages',
    );

    expect($relation)->not->toBeNull()
        ->and($relation->from)->toBe('users')
        ->and($relation->type)->toBe(RelationType::Guessed)
        ->and($relation->columns)->toBe(['user_id']);
});

it('omits guessed relations when disabled', function (): void {
    $relations = collect(buildSchema(guessRelationships: false)->relations);

    expect($relations->contains(fn ($r): bool => $r->type === RelationType::Guessed))->toBeFalse();
});

it('builds morph relations from configured mappings', function (): void {
    $schema = buildSchema(polymorphicRelationships: ['reviews.reviewable' => ['posts', 'videos']]);

    $morphs = collect($schema->relations)->filter(fn ($r): bool => $r->type === RelationType::Morph);

    expect($morphs->pluck('from')->all())->toBe(['posts', 'videos'])
        ->and($morphs->first()->to)->toBe('reviews')
        ->and($morphs->first()->morphName)->toBe('reviewable')
        ->and($schema->unmappedMorphs())->not->toContain('reviews.reviewable');
});

it('reports unmapped morph pairs', function (): void {
    expect(buildSchema()->unmappedMorphs())->toBe(['attachments.attachable', 'reviews.reviewable']);
});

it('flags relation columns without a supporting index', function (): void {
    $relations = collect(buildSchema()->relations);

    $guessed = $relations->first(fn ($r): bool => $r->to === 'ai_messages');
    $constrained = $relations->first(fn ($r): bool => $r->to === 'posts' && $r->columns === ['user_id']);

    expect($guessed->indexed)->toBeFalse()
        ->and($constrained->indexed)->toBeTrue();
});

it('accepts only indexes that lead with the relation column', function (): void {
    SchemaFacade::create('metrics', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('customer_id');
        $table->timestamp('recorded_at')->nullable();
        $table->index(['user_id', 'recorded_at']);
        $table->index(['recorded_at', 'customer_id']);
    });

    $relations = collect(buildSchema()->relations)
        ->where('to', 'metrics')
        ->keyBy(fn ($r): string => $r->columns[0]);

    expect($relations['user_id']->indexed)->toBeTrue()
        ->and($relations['customer_id']->indexed)->toBeFalse();

    SchemaFacade::drop('metrics');
});

it('flags morph pairs without a supporting index', function (): void {
    SchemaFacade::create('badges', function (Blueprint $table): void {
        $table->id();
        $table->string('badgeable_type');
        $table->unsignedBigInteger('badgeable_id');
    });

    $mapped = buildSchema(polymorphicRelationships: ['badges.badgeable' => ['posts']]);
    $morph = collect($mapped->relations)->first(fn ($r): bool => $r->to === 'badges');

    expect($morph->indexed)->toBeFalse()
        ->and($mapped->table('badges')->unindexedMorphs)->toBe(['badgeable'])
        ->and($mapped->table('reviews')->unindexedMorphs)->toBe([])
        ->and((new MermaidErdRenderer)->render($mapped))
        ->toContain('posts ||--o{ badges : "morphMany via badgeable, no index"')
        ->and((new MermaidErdRenderer)->render(buildSchema()))
        ->toContain('%%   badges.badgeable (badgeable_type + badgeable_id, no index)');

    SchemaFacade::drop('badges');
});

it('annotates hasOne relations without a unique index', function (): void {
    SchemaFacade::create('profiles', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id')->index();
    });

    $schema = new SchemaBuilder(
        new DatabaseInformationService(app('db')->connection()),
        models: new ModelScan(relations: [new ScannedRelation('users', 'profiles', 'user_id', 'hasOne')]),
    )->build();

    expect((new MermaidErdRenderer)->render($schema))
        ->toContain('users ||--|| profiles : "hasOne via user_id, no unique index"');

    SchemaFacade::drop('profiles');
});

it('exposes a graph representation for filtering', function (): void {
    $graph = buildSchema()->toGraph();

    expect($graph['tables']['users'])->toBe(['id', 'name', 'email', 'created_at', 'updated_at'])
        ->and($graph['pivots'])->toContain('post_tag')
        ->and($graph['pivots'])->toContain('coupon_order')
        ->and($graph['pivots'])->not->toContain('stocks')
        ->and($graph['edges'])->toContain(['posts', 'comments'])
        ->and($graph['edges'])->toContain(['users', 'ai_messages'])
        ->and(collect($graph['edges'])->duplicates()->all())->toBe([]);
});
