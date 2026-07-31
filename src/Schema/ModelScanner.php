<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Schema;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

/**
 * Discovers Eloquent models and extracts diagram-relevant metadata. Only
 * methods with an explicit Morph* or Attribute return type are ever invoked;
 * a model that fails to instantiate or a relation that throws is skipped.
 */
class ModelScanner
{
    /**
     * @param  string[]  $paths
     */
    public function __construct(private readonly array $paths) {}

    public function scan(): ModelScan
    {
        $models = [];
        $morphs = [];

        foreach ($this->discoverModelClasses() as $class) {
            try {
                $this->inspect($class, $models, $morphs);
            } catch (\Throwable) {
                // A broken model must never kill diagram generation.
            }
        }

        return new ModelScan($models, array_map(
            fn (array $targets): array => array_values(array_unique($targets)),
            $morphs,
        ));
    }

    /**
     * @param  class-string  $class
     * @param  array<string, ModelMetadata>  $models
     * @param  array<string, string[]>  $morphs
     */
    private function inspect(string $class, array &$models, array &$morphs): void
    {
        $reflection = new \ReflectionClass($class);
        if (!$reflection->isSubclassOf(Model::class) || !$reflection->isInstantiable()) {
            return;
        }

        /** @var Model $model */
        $model = new $class;
        $table = $model->getTable();

        $accessors = [];
        $mutators = [];

        foreach ($reflection->getMethods() as $method) {
            if ($method->isStatic() || str_starts_with($method->getDeclaringClass()->getName(), 'Illuminate\\')) {
                continue;
            }

            $name = $method->getName();

            // Legacy accessors/mutators receive the raw value as parameter.
            if (preg_match('/^get(.+)Attribute$/', $name, $matches)) {
                $accessors[] = Str::snake($matches[1]);

                continue;
            }
            if (preg_match('/^set(.+)Attribute$/', $name, $matches)) {
                $mutators[] = Str::snake($matches[1]);

                continue;
            }

            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (!$returnType instanceof \ReflectionNamedType || $returnType->isBuiltin()) {
                continue;
            }
            $typeName = $returnType->getName();

            if (is_a($typeName, Attribute::class, true)) {
                try {
                    /** @var Attribute<mixed, mixed> $attribute */
                    $attribute = $method->invoke($model);
                } catch (\Throwable) {
                    continue;
                }
                $attributeName = Str::snake($name);
                if ($attribute->get !== null) {
                    $accessors[] = $attributeName;
                }
                if ($attribute->set !== null) {
                    $mutators[] = $attributeName;
                }

                continue;
            }

            if (is_a($typeName, MorphOne::class, true)
                || is_a($typeName, MorphMany::class, true)
                || is_a($typeName, MorphToMany::class, true)) {
                try {
                    $relation = $method->invoke($model);
                } catch (\Throwable) {
                    continue;
                }

                $morphName = Str::beforeLast($relation->getMorphType(), '_type');

                if ($relation instanceof MorphToMany) {
                    $childTable = $relation->getTable();
                    $target = $relation->getInverse() ? $relation->getRelated()->getTable() : $table;
                } else {
                    $childTable = $relation->getRelated()->getTable();
                    $target = $table;
                }

                $morphs["{$childTable}.{$morphName}"][] = $target;
            }
        }

        // First discovered model wins when several share a table.
        $models[$table] ??= new ModelMetadata(
            class: $class,
            casts: $this->declaredCasts($model),
            accessors: array_values(array_unique($accessors)),
            mutators: array_values(array_unique($mutators)),
        );
    }

    /**
     * @return array<string, string>
     */
    private function declaredCasts(Model $model): array
    {
        $casts = $model->getCasts();

        // getCasts() adds an implicit 'int' cast for incrementing keys — noise.
        if ($model->getIncrementing() && ($casts[$model->getKeyName()] ?? null) === 'int') {
            unset($casts[$model->getKeyName()]);
        }

        return $casts;
    }

    /**
     * @return list<class-string>
     */
    private function discoverModelClasses(): array
    {
        $classes = [];

        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            );
            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
            sort($files);

            foreach ($files as $file) {
                $class = $this->classFromFile($file);
                if ($class !== null) {
                    $classes[] = $class;
                }
            }
        }

        return $classes;
    }

    /**
     * @return class-string|null
     */
    private function classFromFile(string $file): ?string
    {
        $contents = (string) file_get_contents($file);

        if (!preg_match('/^namespace\s+([^;\s]+)\s*;/m', $contents, $namespace)) {
            return null;
        }
        if (!preg_match('/^\s*(?:final\s+|abstract\s+|readonly\s+)*class\s+(\w+)/m', $contents, $class)) {
            return null;
        }

        $fqcn = $namespace[1].'\\'.$class[1];

        return class_exists($fqcn) ? $fqcn : null;
    }
}
