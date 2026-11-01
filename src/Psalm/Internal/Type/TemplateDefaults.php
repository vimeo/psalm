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
use Psalm\Internal\TypeVisitor\TemplateTypeCollector;
use Psalm\Internal\TypeVisitor\TypeScanner;
use Psalm\Issue\InvalidDocblock;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_key_first;
use function array_keys;
use function count;
use function reset;
use function strtolower;

/**
 * What a template with a default (`@template T of Bound = Default`) stands for when a use of its class leaves it out
 * or a call of its function-like doesn't infer it: the default, with the earlier templates standing for what they are.
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
     * The type arguments of a use giving only some of them (`Foo<A>`), plus the defaults of the trailing type templates
     * it leaves out, marked as written where the use is ($from_docblock). A use with no type arguments (`Foo`,
     * `Foo[pure]`) stands for the bounds and is left alone.
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
     * {@see self::fillTypeArguments()}, with $fill_default filling the generic types in each default. A default that
     * comes back to its own slot (`@template TNext = Node<T>`) leaves that slot and the later ones out of the generated
     * use, for readers to fill when they reach it, so that filling ends.
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
     * A resolved template default expanded in the scope of its class, with its slot marked as being filled meanwhile
     * ({@see self::fillTypeArgumentsWith()}).
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
     * What the templates of a class are bound to by a use binding some by name (`@extends Foo<A>`) or none (a class
     * without `@extends`, `new Foo()` without a constructor): their defaults and, with $others_to_bound, the others'
     * bounds. Without $purity_defaults, purity templates are treated as having no default, as in a `new` type.
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
     * Binds the templates the call left to their default ({@see self::isUninferred()}) in declaration order, the order
     * of $template_defaults, so each default sees those before it. Like a constraint fallback, a default is a
     * placeholder that reconciliation does not check.
     *
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public static function bindUninferred(
        TemplateResult $template_result,
        FunctionLikeStorage $storage,
        Codebase $codebase,
    ): void {
        $template_types = $storage->template_types ?? [];

        foreach ($storage->template_defaults as $template_name => $default) {
            $defining_id = array_key_first($template_types[$template_name] ?? []);

            if ($defining_id === null || !self::isUninferred($template_result, $template_name, $defining_id)) {
                continue;
            }

            // a template the call leaves unbound and without a default is `never` in the return type, so in the default
            $resolving_result = clone $template_result;

            foreach ($template_types as $name => $other_type_map) {
                $resolving_result->lower_bounds[$name][array_key_first($other_type_map)]
                    ??= [new TemplateBound(Type::getNever())];
            }

            $default_bound = new TemplateBound(self::resolve($default, $resolving_result, $codebase));
            $default_bound->from_constraint_fallback = true;

            $template_result->lower_bounds[$template_name][$defining_id] = [$default_bound];
        }
    }

    /**
     * The default with the templates it uses replaced by what they are bound to
     *
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public static function resolve(Union $default, TemplateResult $template_result, ?Codebase $codebase): Union
    {
        if ($template_result->lower_bounds === [] || self::getUsedTemplates($default) === []) {
            return $default;
        }

        return TemplateInferredTypeReplacer::replace($default, $template_result, $codebase);
    }

    /**
     * The templates a default uses, also through `key-of<T>`, `value-of<T>`, `properties-of<T>` and `T[K]`
     *
     * @return list<TTemplateParam>
     * @psalm-pure
     */
    private static function getUsedTemplates(Union $default): array
    {
        $collector = new TemplateTypeCollector(true);
        $collector->traverseArray($default->getAtomicTypes());

        return $collector->getTemplateTypes();
    }

    /**
     * Reports the defaults naming undefined classes, as for bounds, and those outside their template's bound, where
     * they are declared. Earlier templates stay templates there, so `@template U of T = T` is within its bound.
     *
     * @param array<string, non-empty-array<string, Union>> $template_types
     * @param array<string, Union> $template_defaults
     * @param array<string, CodeLocation> $locations
     * @param array<string> $suppressed_issues
     */
    public static function checkBounds(
        StatementsSource $source,
        array $template_types,
        array $template_defaults,
        array $locations,
        ?string $self_class,
        ?string $parent_class,
        string $owner,
        array $suppressed_issues,
    ): void {
        $codebase = $source->getCodebase();

        foreach ($locations as $template_name => $location) {
            $bound = reset($template_types[$template_name]);
            $default = $template_defaults[$template_name] ?? null;

            if ($default === null
                || !$default->check($source, $location, $suppressed_issues, [], false)
                || $bound->isMixed()
            ) {
                continue;
            }

            $default = TypeExpander::expandUnion($codebase, $default, $self_class, $self_class, $parent_class);
            $bound = TypeExpander::expandUnion($codebase, $bound, $self_class, $self_class, $parent_class);

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
     * Parses the defaults of a declaration's templates in declaration order, with their locations and the issues
     * dropping the unusable ones: empty, invalid, or using a template of the declaration declared after it (which
     * rules out cycles). Purity templates take no type default, but a default may use them wherever they are declared.
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

            foreach (self::getUsedTemplates($default) as $used_template) {
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
     * Whether the arguments of a call or `new` leave a template to its default: no upper bound, and no lower bound but
     * stand-ins for arguments saying nothing about it ({@see TemplateBound::$from_template_free_argument}).
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
     * The type with the defaults left out of its generic types filled in at any depth, for the readers of stored types
     * that don't expand them.
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
     * How many type arguments a use of the class must give, worded for MissingTemplateParam: "at least N" when
     * trailing templates with a default may be left out.
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
