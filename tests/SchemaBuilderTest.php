<?php declare(strict_types=1);

use Bambamboole\LaravelMermaidErd\DatabaseInformationService;
use Bambamboole\LaravelMermaidErd\Schema\RelationType;
use Bambamboole\LaravelMermaidErd\Schema\Schema;
use Bambamboole\LaravelMermaidErd\Schema\SchemaBuilder;

function buildSchema(array $polymorphicRelationships = [], bool $guessRelationships = true): Schema
{
    return (new SchemaBuilder(
        new DatabaseInformationService(app('db')->connection()),
        $polymorphicRelationships,
        $guessRelationships,
    ))->build();
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
        ->and($schema->unmappedMorphs())->toBe([]);
});

it('reports unmapped morph pairs', function (): void {
    expect(buildSchema()->unmappedMorphs())->toBe(['reviews.reviewable']);
});
