<?php

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

it('writes diagram to a file', function () {
    $path = tempnam(sys_get_temp_dir(), 'mermaid_test_');

    $this->artisan('generate:mermaid-erd', ['--output' => 'file', '--path' => $path])
        ->assertSuccessful();

    expect(file_get_contents($path))->toBe(loadFixture('expected-file-output.md'));

    unlink($path);
});

it('injects diagram into readme between tags', function () {
    $path = copyFixtureToTemp('readme-with-tags.md');

    $this->artisan('generate:mermaid-erd', ['--output' => 'readme', '--path' => $path])
        ->assertSuccessful();

    expect(file_get_contents($path))->toBe(loadFixture('expected-readme-with-tags.md'));

    unlink($path);
});

it('replaces existing diagram content between readme tags', function () {
    $path = copyFixtureToTemp('readme-with-old-content.md');

    $this->artisan('generate:mermaid-erd', ['--output' => 'readme', '--path' => $path])
        ->assertSuccessful();

    $content = file_get_contents($path);

    expect($content)
        ->not->toContain('old content here')
        ->toContain('erDiagram');

    unlink($path);
});

it('appends diagram with heading when readme tags are missing', function () {
    $path = copyFixtureToTemp('readme-without-tags.md');

    $this->artisan('generate:mermaid-erd', ['--output' => 'readme', '--path' => $path])
        ->assertSuccessful();

    expect(file_get_contents($path))->toBe(loadFixture('expected-readme-without-tags.md'));

    unlink($path);
});

it('fails when readme file does not exist', function () {
    $this->artisan('generate:mermaid-erd', ['--output' => 'readme', '--path' => '/tmp/nonexistent_readme.md'])
        ->assertFailed();
});
