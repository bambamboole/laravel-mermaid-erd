<?php declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

function copyFixtureToTemp(string $name): string
{
    $path = tempnam(sys_get_temp_dir(), 'mermaid_test_');
    copy(__DIR__.'/fixtures/'.$name, $path);

    return $path;
}

function assertDiagramStructure(string $output): void
{
    expect($output)
        ->toContain('erDiagram')
        ->toMatch('/categories\[.*?\] \{/')
        ->toMatch('/comments\[.*?\] \{/')
        ->toMatch('/posts\[.*?\] \{/')
        ->toMatch('/tags\[.*?\] \{/')
        ->toMatch('/users\[.*?\] \{/')
        ->toMatch('/post_tag\[.*?\] \{/')
        // PK/FK/UK markers (type names differ per driver, e.g. integer vs int8 vs bigint)
        ->toMatch('/users\[.*?\] \{[^}]*\w+ id PK/s')
        ->toMatch('/users\[.*?\] \{[^}]*\w+ email UK/s')
        ->toMatch('/posts\[.*?\] \{[^}]*\w+ id PK/s')
        ->toMatch('/posts\[.*?\] \{[^}]*\w+ user_id FK/s')
        ->toMatch('/tags\[.*?\] \{[^}]*\w+ slug UK/s')
        ->toMatch('/comments\[.*?\] \{[^}]*\w+ post_id FK/s')
        ->toMatch('/comments\[.*?\] \{[^}]*\w+ user_id FK/s')
        // Relationships with cascade info
        ->toContain('post_tag : "pivot, has many via post_id, cascade delete"')
        ->toContain('post_tag : "pivot, has many via tag_id, cascade delete"')
        ->toContain('comments : "has many via user_id, cascade delete"')
        ->toContain('comments : "has many via post_id, cascade delete"')
        ->toContain('posts : "has many via user_id, cascade delete"');
}

it('outputs diagram to stdout', function (): void {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);

    assertDiagramStructure(Artisan::output());
});

it('writes diagram to a new file with tags', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'mermaid_test_');
    unlink($path);

    $this->artisan('generate:mermaid-erd', ['--output' => 'file', '--path' => $path])
        ->assertSuccessful();

    $content = file_get_contents($path);

    expect($content)
        ->toStartWith("## ERD\n")
        ->toContain('<!-- mermaid-erd-start -->')
        ->toContain('<!-- mermaid-erd-end -->');
    assertDiagramStructure($content);

    unlink($path);
});

it('injects diagram into file between existing tags', function (): void {
    $path = copyFixtureToTemp('readme-with-tags.md');

    $this->artisan('generate:mermaid-erd', ['--output' => 'file', '--path' => $path])
        ->assertSuccessful();

    $content = file_get_contents($path);

    expect($content)
        ->toStartWith('# My Project')
        ->toContain('<!-- mermaid-erd-start -->')
        ->toContain('<!-- mermaid-erd-end -->')
        ->toContain('## Other content');
    assertDiagramStructure($content);

    unlink($path);
});

it('only fills the first tag pair when a file contains several', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'mermaid_test_');
    file_put_contents($path, implode("\n", [
        '# My Project',
        '<!-- mermaid-erd-start -->',
        '<!-- mermaid-erd-end -->',
        '## Usage example documenting the tags',
        '<!-- mermaid-erd-start -->',
        '<!-- mermaid-erd-end -->',
    ]));

    $this->artisan('generate:mermaid-erd', ['--output' => 'file', '--path' => $path])
        ->assertSuccessful();

    $content = file_get_contents($path);

    expect(substr_count($content, '```mermaid'))->toBe(1)
        ->and($content)->toContain("## Usage example documenting the tags\n<!-- mermaid-erd-start -->\n<!-- mermaid-erd-end -->");

    unlink($path);
});

it('replaces existing diagram content between tags', function (): void {
    $path = copyFixtureToTemp('readme-with-old-content.md');

    $this->artisan('generate:mermaid-erd', ['--output' => 'file', '--path' => $path])
        ->assertSuccessful();

    $content = file_get_contents($path);

    expect($content)
        ->not->toContain('old content here')
        ->toContain('erDiagram');

    unlink($path);
});

it('appends diagram with heading when tags are missing', function (): void {
    $path = copyFixtureToTemp('readme-without-tags.md');

    $this->artisan('generate:mermaid-erd', ['--output' => 'file', '--path' => $path])
        ->assertSuccessful();

    $content = file_get_contents($path);

    expect($content)
        ->toStartWith('# My Project')
        ->toContain('## ERD')
        ->toContain('<!-- mermaid-erd-start -->')
        ->toContain('<!-- mermaid-erd-end -->');
    assertDiagramStructure($content);

    unlink($path);
});

it('supports the --connection option', function (): void {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout', '--connection' => config('database.default')]);

    expect(Artisan::output())->toContain('erDiagram');
});

it('detects soft-delete columns and annotates them', function (): void {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->toMatch('/videos\[.*?\] \{[^}]*\w+ deleted_at "soft-delete, nullable"/s');
});

it('detects polymorphic column pairs and annotates them', function (): void {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->toMatch('/reviews\[.*?\] \{[^}]*\w+ reviewable_type "polymorphic"/s')
        ->toMatch('/reviews\[.*?\] \{[^}]*\w+ reviewable_id "polymorphic"/s');
});

it('renders polymorphic relationships from config', function (): void {
    config()->set('mermaid-erd.polymorphic_relationships', [
        'reviews.reviewable' => ['posts', 'videos'],
    ]);

    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->toContain('posts ||--o{ reviews : "morphMany via reviewable"')
        ->toContain('videos ||--o{ reviews : "morphMany via reviewable"');
});

it('supports the --tables option', function (): void {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout', '--tables' => 'users,posts']);

    $output = Artisan::output();

    expect($output)
        ->toMatch('/users\[.*?\] \{/')
        ->toMatch('/posts\[.*?\] \{/')
        ->not->toMatch('/comments\[.*?\] \{/')
        ->not->toMatch('/tags\[.*?\] \{/')
        ->not->toMatch('/post_tag\[.*?\] \{/');
});

it('renders pivot tables as full entities with FK columns', function (): void {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->toMatch('/post_tag\[.*?\] \{[^}]*\w+ post_id FK/s')
        ->toMatch('/post_tag\[.*?\] \{[^}]*\w+ tag_id FK/s');
});

it('uses nullable parent-side cardinality for nullable foreign keys', function (): void {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->toContain('categories |o--o{ categories');
});

it('renders self-referential relationships with self-ref label', function (): void {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->toContain('self-ref via parent_id');
});

it('includes overall table and column stats in title', function (): void {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->toMatch('/^---\ntitle: \d+ tables · \d+ columns\n---\n/');
});

it('includes per-table column count in table header', function (): void {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->toContain('users["users (5)"]')
        ->toContain('posts["posts (6)"]');
});

it('guesses relationships for _id columns without FK constraints', function (): void {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->toContain('users ||--o{ ai_messages : "guessed has many via user_id"');
});

it('does not guess relationships when config is disabled', function (): void {
    config()->set('mermaid-erd.guess_relationships', false);

    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->not->toContain('guessed has many');
});

it('shows unmapped polymorphic relations as comments', function (): void {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->toContain("%% Unmapped polymorphic relations (add to config 'mermaid-erd.polymorphic_relationships'):")
        ->toContain('%%   reviews.reviewable (reviewable_type + reviewable_id)');
});

it('does not show mapped polymorphic relations in unmapped comments', function (): void {
    config()->set('mermaid-erd.polymorphic_relationships', [
        'reviews.reviewable' => ['posts', 'videos'],
    ]);

    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->not->toContain('%%   reviews.reviewable');
});
