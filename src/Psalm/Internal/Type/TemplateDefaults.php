<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Closure;
use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\TypeVisitor\TemplateDefaultFiller;
use Psalm\Internal\TypeVisitor\TypeScanner;
use Psalm\Issue\InvalidDocblock;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Union;

use function array_key_first;
use function array_keys;
use function count;
use function reset;
use function strtolower;

/**
 * What a template left out of a use of its class stands for when it has a default
 * (`@template T of Bound = Default`): the default, in which the templates declared before it stand for
 * what they are there.
 *
 * @internal
 */
final class TemplateDefaults
{
    /**
     * The template slots (`class::template`) whose defaults are being filled in
     *
     * @var array<string, true>
     */
    private static array $filling = [];

    /**
     * The type arguments of a use of the class that gives some of them (`Foo<A>`), with the defaults of the
     * trailing type templates it leaves out, written where the use is ($from_docblock). A use without type
     * arguments (`Foo`, `Foo[pure]`) is left alone: it stands for the bounds.
     *
     * @param list<Union> $type_params
     * @return ($type_params is non-empty-list ? non-empty-list<Union> : list<Union>)
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public static function fillTypeArguments(
        array $type_params,
        ClassLikeStorage $storage,
        ?Codebase $codebase,
        bool $from_docblock,
    ): array {
        if ($storage->template_defaults === [] || count($type_params) >= count($storage->template_types ?? [])) {
            return $type_params;
        }

        [$type_args, $purity_args] = PurityArguments::split($type_params);

        if ($type_args === []) {
            return $type_params;
        }

        $given = [];
        $i = 0;

        foreach ($storage->template_types ?? [] as $template_name => $type_map) {
            $defining_class = array_key_first($type_map);

            if ($i < count($type_args)) {
                $given[$template_name][$defining_class] = $type_args[$i++];

                continue;
            }

            if (!isset($storage->template_defaults[$template_name])
                || isset($storage->purity_templates[$template_name])
            ) {
                break;
            }

            $type_args[] = $given[$template_name][$defining_class] = self::resolve(
                $storage->template_defaults[$template_name],
                new TemplateResult([], $given),
                $codebase,
            )->setFromDocblock($from_docblock);
            $i++;
        }

        return [...$type_args, ...$purity_args];
    }

    /**
     * {@see self::fillTypeArguments()}, with each default expanded in the scope of its class (see
     * {@see self::fillTypeArgumentsWith()}).
     *
     * @param list<Union> $type_params
     * @return ($type_params is non-empty-list ? non-empty-list<Union> : list<Union>)
     * @psalm-impure
     */
    public static function fillAndExpandTypeArguments(
        array $type_params,
        ClassLikeStorage $storage,
        Codebase $codebase,
        bool $from_docblock,
    ): array {
        return self::fillTypeArgumentsWith(
            $type_params,
            $storage,
            $codebase,
            $from_docblock,
            static fn(Union $default): Union => TypeExpander::expandUnion(
                $codebase,
                $default,
                $storage->name,
                $storage->name,
                $storage->parent_class,
            ),
        );
    }

    /**
     * {@see self::fillTypeArguments()}, with $fill_default filling in the uses of generic types in each default.
     * Doing so may come upon the template slot being filled again (`@template TNext = Node<T>`): that generated
     * use is left without it and the slots after it, for the readers to fill when they get to it, so that
     * filling ends.
     *
     * @param list<Union> $type_params
     * @param Closure(Union): Union $fill_default
     * @return ($type_params is non-empty-list ? non-empty-list<Union> : list<Union>)
     * @psalm-impure
     */
    public static function fillTypeArgumentsWith(
        array $type_params,
        ClassLikeStorage $storage,
        ?Codebase $codebase,
        bool $from_docblock,
        Closure $fill_default,
    ): array {
        $filled_type_params = self::fillTypeArguments($type_params, $storage, $codebase, $from_docblock);

        if ($filled_type_params === $type_params) {
            return $type_params;
        }

        [$type_args, $purity_args] = PurityArguments::split($type_params);
        $given_count = count($type_args);
        $filled_type_args = PurityArguments::split($filled_type_params)[0];

        foreach (array_keys($storage->template_types ?? []) as $i => $template_name) {
            if ($i < $given_count) {
                continue;
            }

            if (!isset($filled_type_args[$i])
                || isset(self::$filling[strtolower($storage->name) . '::' . $template_name])
            ) {
                break;
            }

            $default = $filled_type_args[$i];
            $type_args[] = self::inSlot($storage, $template_name, static fn(): Union => $fill_default($default));
        }

        return [...$type_args, ...$purity_args];
    }

    /**
     * A resolved default of a template of the class, expanded in the scope of the class, with its slot marked as
     * being filled in meanwhile (see {@see self::fillTypeArgumentsWith()}).
     *
     * @psalm-impure
     */
    public static function expandDefault(
        Codebase $codebase,
        Union $default,
        ClassLikeStorage $storage,
        string $template_name,
    ): Union {
        return self::inSlot(
            $storage,
            $template_name,
            static fn(): Union => TypeExpander::expandUnion(
                $codebase,
                $default,
                $storage->name,
                $storage->name,
                $storage->parent_class,
            ),
        );
    }

    /**
     * @param Closure(): Union $fill
     * @psalm-impure
     */
    private static function inSlot(ClassLikeStorage $storage, string $template_name, Closure $fill): Union
    {
        $slot = strtolower($storage->name) . '::' . $template_name;
        self::$filling[$slot] = true;

        try {
            return $fill();
        } finally {
            unset(self::$filling[$slot]);
        }
    }

    /**
     * What the templates of a class are bound to by a use that binds some of them by name (`@extends Foo<A>`),
     * or none (no `@extends`, `new Foo()` without a constructor): those with a default are bound to it and,
     * with $others_to_bound, the others to their bound. Without $purity_defaults, the purity templates are
     * bound to their bound too, as in a `new` type.
     *
     * @param array<string, Union> $given
     * @return array<string, Union>
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public static function bindOmitted(
        ClassLikeStorage $storage,
        array $given,
        bool $others_to_bound,
        bool $purity_defaults = true,
    ): array {
        $bound = [];

        foreach ($storage->template_types ?? [] as $template_name => $type_map) {
            $defining_class = array_key_first($type_map);

            if (!isset($given[$template_name])) {
                if (isset($storage->template_defaults[$template_name])
                    && ($purity_defaults || !isset($storage->purity_templates[$template_name]))
                ) {
                    $given[$template_name] = self::resolve(
                        $storage->template_defaults[$template_name],
                        new TemplateResult([], $bound),
                        null,
                    );
                } elseif ($others_to_bound) {
                    $given[$template_name] = $type_map[$defining_class];
                } else {
                    continue;
                }
            }

            $bound[$template_name][$defining_class] = $given[$template_name];
        }

        return $given;
    }

    /**
     * The default with the templates it uses replaced by what they are bound to
     *
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public static function resolve(Union $default, TemplateResult $template_result, ?Codebase $codebase): Union
    {
        if ($template_result->lower_bounds === [] || $default->getTemplateTypes() === []) {
            return $default;
        }

        return TemplateInferredTypeReplacer::replace($default, $template_result, $codebase);
    }

    /**
     * Reports the defaults that are not within the bounds of their templates, where they are declared. The
     * templates declared before a default stay templates there: `@template U of T = T` is within its bound.
     *
     * @param array<string, non-empty-array<string, Union>> $template_types
     * @param array<string, Union> $template_defaults
     * @param array<string, CodeLocation> $locations
     * @param array<string> $suppressed_issues
     */
    public static function checkBounds(
        Codebase $codebase,
        array $template_types,
        array $template_defaults,
        array $locations,
        ?string $self_class,
        string $owner,
        array $suppressed_issues,
    ): void {
        foreach ($locations as $template_name => $location) {
            $bound = reset($template_types[$template_name]);
            $default = $template_defaults[$template_name] ?? null;

            if ($default === null || $bound->isMixed()) {
                continue;
            }

            $default = TypeExpander::expandUnion($codebase, $default, $self_class, $self_class, null);
            $bound = TypeExpander::expandUnion($codebase, $bound, $self_class, $self_class, null);

            if (!UnionTypeComparator::isContainedBy($codebase, $default, $bound)) {
                IssueBuffer::maybeAdd(
                    new InvalidDocblock(
                        'The default ' . $default->getId() . ' of template ' . $template_name
                            . ' is not within its bound ' . $bound->getId() . ' in docblock for ' . $owner,
                        $location,
                    ),
                    $suppressed_issues,
                );
            }
        }
    }

    /**
     * The defaults of the templates of a declaration, parsed in the order the templates are declared, and where
     * they are, with the issues about those that can't be used: an empty or invalid type, or one that uses a
     * template of the declaration not declared before it (which makes cycles impossible). Such a template has no
     * default. Purity templates take no type default, and a default may use them wherever they are declared.
     *
     * @param array<string, array{type: string, line_number: int}> $entries
     * @param array<string, array<string, Union>> $template_types the templates of the declaration
     * @param array<string, array<string, Union>> $scope_template_types the templates the defaults may use
     * @param array<string, true> $purity_templates
     * @param array<string, TypeAlias> $type_aliases
     * @return array{array<string, Union>, array<string, CodeLocation>, list<InvalidDocblock>}
     */
    public static function parseDefaults(
        array $entries,
        array $template_types,
        array $scope_template_types,
        array $purity_templates,
        Aliases $aliases,
        array $type_aliases,
        ?string $self_fqcln,
        ?string $parent_fqcln,
        CodeLocation $location,
        string $owner,
        TypeScanner $type_scanner,
    ): array {
        $defaults = [];
        $locations = [];
        $issues = [];
        $declared = [];

        foreach ($template_types as $template_name => $type_map) {
            $entry = $entries[$template_name] ?? null;
            $declared_before = $declared;
            $declared[$template_name] = true;

            if ($entry === null || isset($purity_templates[$template_name])) {
                continue;
            }

            $default_location = $location->setCommentLine($entry['line_number']);

            if ($entry['type'] === '') {
                $issues[] = new InvalidDocblock(
                    'Missing default after = for template ' . $template_name . ' in docblock for ' . $owner,
                    $default_location,
                );

                continue;
            }

            try {
                $default = TypeParser::parseTokens(
                    TypeTokenizer::getFullyQualifiedTokens(
                        CommentAnalyzer::sanitizeDocblockType($entry['type']),
                        $aliases,
                        $scope_template_types,
                        $type_aliases,
                        $self_fqcln,
                        $parent_fqcln,
                    ),
                    null,
                    $scope_template_types,
                    $type_aliases,
                );
            } catch (TypeParseTreeException $e) {
                $issues[] = new InvalidDocblock(
                    'Invalid default of template ' . $template_name . ': ' . $e->getMessage()
                        . ' in docblock for ' . $owner,
                    $default_location,
                );

                continue;
            }

            $type_scanner->traverseArray($default->getAtomicTypes());

            foreach ($default->getTemplateTypes() as $used_template) {
                if ($used_template->defining_class === array_key_first($type_map)
                    && !isset($declared_before[$used_template->param_name])
                    && !isset($purity_templates[$used_template->param_name])
                ) {
                    $issues[] = new InvalidDocblock(
                        'The default of template ' . $template_name . ' uses template '
                            . $used_template->param_name . ', which is not declared before it, in docblock for '
                            . $owner,
                        $default_location,
                    );

                    continue 2;
                }
            }

            $defaults[$template_name] = $default;
            $locations[$template_name] = $default_location;
        }

        return [$defaults, $locations, $issues];
    }

    /**
     * Whether the arguments of a call or `new` leave a template to its default: they gave it no upper bound, and no
     * lower bound but the stand-ins for arguments that matched a part of the parameter type naming no template
     * (`null` for `(callable(): T)|null`, a string for `(callable(): T)|string`). An argument matching the part
     * that names the template without telling what it is (a bare `callable`, a bare generic object) infers its
     * bound instead.
     *
     * @psalm-mutation-free
     */
    public static function isUninferred(
        TemplateResult $template_result,
        string $template_name,
        string $defining_id,
    ): bool {
        if (isset($template_result->upper_bounds[$template_name][$defining_id])) {
            return false;
        }

        foreach ($template_result->lower_bounds[$template_name][$defining_id] ?? [] as $lower_bound) {
            if (!$lower_bound->from_template_free_argument) {
                return false;
            }
        }

        return true;
    }

    /**
     * The type with the generic types in it written with fewer type arguments than their class has templates given
     * the defaults they leave out, for the readers of stored types that don't expand them.
     *
     * @psalm-impure
     */
    public static function fillNested(
        Union $type,
        ClassLikeStorageProvider $classlike_storage_provider,
        ?Codebase $codebase,
    ): Union {
        (new TemplateDefaultFiller($classlike_storage_provider, $codebase))->traverse($type);

        return $type;
    }

    /**
     * How many type arguments a use of the class must give, as issues about missing ones word it: at least
     * those before the trailing defaults (of type or purity templates) when some may be left out, all of them
     * otherwise.
     *
     * @psalm-mutation-free
     */
    public static function getExpectedParamCount(ClassLikeStorage $storage): string
    {
        $param_count = count($storage->template_types ?? []);
        $required_param_count = $storage->getRequiredTemplateParamCount();

        return $required_param_count < $param_count
            ? 'at least ' . $required_param_count
            : (string) $param_count;
    }
}
