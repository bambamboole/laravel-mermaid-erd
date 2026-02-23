<?php declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

function loadFixture(string $name): string
{
    return file_get_contents(__DIR__.'/fixtures/'.$name);
}

function copyFixtureToTemp(string $name): string
{
    $path = tempnam(sys_get_temp_dir(), 'mermaid_test_');
    copy(__DIR__.'/fixtures/'.$name, $path);

    return $path;
}

it('outputs diagram to stdout', function () {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout']);

    expect(Artisan::output())->toBe(loadFixture('expected-diagram.txt')."\n");
});

it('writes diagram to a new file with tags', function () {
    $path = tempnam(sys_get_temp_dir(), 'mermaid_test_');
    unlink($path);

    $this->artisan('generate:mermaid-erd', ['--output' => 'file', '--path' => $path])
        ->assertSuccessful();

    expect(file_get_contents($path))->toBe(loadFixture('expected-new-file.md'));

    unlink($path);
});

it('injects diagram into file between existing tags', function () {
    $path = copyFixtureToTemp('readme-with-tags.md');

    $this->artisan('generate:mermaid-erd', ['--output' => 'file', '--path' => $path])
        ->assertSuccessful();

    expect(file_get_contents($path))->toBe(loadFixture('expected-readme-with-tags.md'));

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

    expect(file_get_contents($path))->toBe(loadFixture('expected-readme-without-tags.md'));

    unlink($path);
});

it('supports the --connection option', function () {
    Artisan::call('generate:mermaid-erd', ['--output' => 'stdout', '--connection' => config('database.default')]);

    expect(Artisan::output())->toContain('erDiagram');
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
