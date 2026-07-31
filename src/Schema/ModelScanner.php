<?php
declare(strict_types=1);
namespace Bambamboole\LaravelMermaidErd\Schema;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Spatie\StructureDiscoverer\Discover;

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
        $paths = array_values(array_filter($this->paths, is_dir(...)));

        if ($paths === []) {
            return [];
        }

        $classes = [];
        foreach (Discover::in(...$paths)->classes()->get() as $class) {
            if (is_string($class) && class_exists($class)) {
                $classes[] = $class;
            }
        }
        sort($classes);

        return $classes;
    }
}
