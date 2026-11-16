<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Type\PurityArguments;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

use function array_keys;
use function array_map;
use function count;
use function reset;
use function strtolower;

/**
 * The arguments of `static<...>` in a type declared by a class belong to the templates of that class. Put them
 * in the order of the templates of the class the method is called on, through its `@extends` mapping. A template
 * of the called class that no template of the declaring class maps to exactly once takes its argument in
 * $called_params, or stands for itself where there are none (the type of a declaration).
 *
 * @internal
 */
final class StaticArgumentsProjector extends MutableTypeVisitor
{
    /**
     * @param array<string, Union> $extended_params how the called class binds the templates of the declaring class
     * @param list<Union>|null $called_params the arguments the called class is used with, if known
     * @psalm-mutation-free
     */
    private function __construct(
        private readonly Codebase $codebase,
        private readonly ClassLikeStorage $declaring_storage,
        private readonly ClassLikeStorage $called_storage,
        private readonly array $extended_params,
        private readonly ?array $called_params,
    ) {
    }

    /**
     * @param list<Union>|null $called_params
     */
    public static function projectType(
        Codebase $codebase,
        Union $type,
        string $declaring_class,
        string $called_class,
        ?array $called_params = null,
    ): Union {
        if (strtolower($declaring_class) === strtolower($called_class)) {
            return $type;
        }

        $static_visitor = new ContainsStaticVisitor(true);
        $static_visitor->traverse($type);

        $provider = $codebase->classlike_storage_provider;

        if (!$static_visitor->matches()
            || !$provider->has($declaring_class)
            || !$provider->has($called_class)
        ) {
            return $type;
        }

        $declaring_storage = $provider->get($declaring_class);
        $called_storage = $provider->get($called_class);
        $extended_params = $called_storage->template_extended_params[$declaring_storage->name] ?? null;

        if ($extended_params === null || $called_storage->template_types === null) {
            return $type;
        }

        (new self($codebase, $declaring_storage, $called_storage, $extended_params, $called_params))
            ->traverse($type);

        return $type;
    }

    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if (!$type instanceof TGenericObject || strtolower($type->value) !== 'static') {
            return null;
        }

        $type_params = $this->project($type->type_params);

        if ($type_params !== []) {
            $type = $type->setTypeParams($type_params);
        }

        return null;
    }

    /**
     * @param list<Union> $type_params
     * @return list<Union>
     */
    private function project(array $type_params): array
    {
        // an imported alias may stand for a purity argument, which is told by what it expands to
        $type_params = array_map(
            fn(Union $param): Union => $param->hasTypeAlias()
                ? TypeExpander::expandUnion($this->codebase, $param, $this->declaring_storage->name, null, null)
                : $param,
            $type_params,
        );

        [$type_args, $purity_args] = PurityArguments::split($type_params);
        $sources = [];

        foreach (array_keys($this->declaring_storage->template_types ?? []) as $i => $declared_name) {
            $extended_param = $this->extended_params[$declared_name] ?? null;
            $atomic = $extended_param?->isSingle() ? $extended_param->getSingleAtomic() : null;

            // `@extends` of an intermediate class leaves its own templates in the mapping
            while ($atomic instanceof TTemplateParam && $atomic->defining_class !== $this->called_storage->name) {
                $extended_param = $this->called_storage
                    ->template_extended_params[$atomic->defining_class][$atomic->param_name] ?? null;
                $atomic = $extended_param?->isSingle() ? $extended_param->getSingleAtomic() : null;
            }

            if ($atomic instanceof TTemplateParam
                && $atomic->defining_class === $this->called_storage->name
                && isset($type_args[$i])
            ) {
                $sources[$atomic->param_name][] = $type_args[$i];
            }
        }

        $projected = [];
        $type_template_count = PurityArguments::countTypeTemplates($this->called_storage);

        foreach ($this->called_storage->template_types ?? [] as $template_name => $type_map) {
            if (count($projected) === $type_template_count) {
                break;
            }

            $projected[] = count($sources[$template_name] ?? []) === 1
                ? $sources[$template_name][0]
                : $this->called_params[count($projected)]
                    ?? new Union([new TTemplateParam($template_name, reset($type_map), $this->called_storage->name)]);
        }

        return [...$projected, ...$purity_args];
    }
}
