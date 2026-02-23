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
        ->toContain('comments {')
        ->toContain('posts {')
        ->toContain('tags {')
        ->toContain('users {')
        ->not->toContain('post_tag {')
        // PK/FK/UK markers
        ->toMatch('/users \{[^}]*integer id PK/s')
        ->toMatch('/users \{[^}]*varchar email UK/s')
        ->toMatch('/posts \{[^}]*integer id PK/s')
        ->toMatch('/posts \{[^}]*integer user_id FK/s')
        ->toMatch('/tags \{[^}]*varchar slug UK/s')
        ->toMatch('/comments \{[^}]*integer post_id FK/s')
        ->toMatch('/comments \{[^}]*integer user_id FK/s')
        // Relationships with cascade info
        ->toContain('posts }o--o{ tags : "post_tag"')
        ->toContain('comments : "has many via user_id, cascade delete"')
        ->toContain('comments : "has many via post_id, cascade delete"')
        ->toContain('posts : "has many via user_id, cascade delete"');
}

it('outputs diagram to stdout', function () {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);

    assertDiagramStructure(Artisan::output());
});

it('writes diagram to a new file with tags', function () {
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

it('injects diagram into file between existing tags', function () {
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

it('replaces existing diagram content between tags', function () {
    $path = copyFixtureToTemp('readme-with-old-content.md');

    $this->artisan('generate:mermaid-erd', ['--output' => 'file', '--path' => $path])
        ->assertSuccessful();

    $content = file_get_contents($path);

    expect($content)
        ->not->toContain('old content here')
        ->toContain('erDiagram');

    unlink($path);
});

it('appends diagram with heading when tags are missing', function () {
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

it('supports the --connection option', function () {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout', '--connection' => config('database.default')]);

    expect(Artisan::output())->toContain('erDiagram');
});

it('detects soft-delete columns and annotates them', function () {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->toMatch('/videos \{[^}]*datetime deleted_at "soft-delete, nullable"/s');
});

it('detects polymorphic column pairs and annotates them', function () {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->toMatch('/reviews \{[^}]*varchar reviewable_type "polymorphic"/s')
        ->toMatch('/reviews \{[^}]*integer reviewable_id "polymorphic"/s');
});

it('renders polymorphic relationships from config', function () {
    config()->set('mermaid-erd.polymorphic_relationships', [
        'reviews.reviewable' => ['posts', 'videos'],
    ]);

    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);
    $output = Artisan::output();

    expect($output)
        ->toContain('posts ||--o{ reviews : "morphMany via reviewable"')
        ->toContain('videos ||--o{ reviews : "morphMany via reviewable"');
});

it('supports the --tables option', function () {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout', '--tables' => 'users,posts']);

    $output = Artisan::output();

    expect($output)
        ->toContain('users {')
        ->toContain('posts {')
        ->not->toContain('comments {')
        ->not->toContain('tags {')
        ->not->toContain('post_tag {');
});
