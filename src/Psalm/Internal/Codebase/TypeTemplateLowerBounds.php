<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Type\Comparator\LowerBoundProof;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TemplateDefaults;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeAlias;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Internal\TypeVisitor\TemplateTypeCollector;
use Psalm\Internal\TypeVisitor\TypeScanner;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidTemplateParam;
use Psalm\Issue\UndefinedDocblockClass;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\TemplateVariance;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Union;

use function array_any;
use function array_key_first;
use function array_keys;
use function array_search;
use function in_array;
use function reset;
use function strtolower;

/**
 * The lower bounds of the type templates (`@template T super Cat`) of the codebase, by what a template is identified
 * by in its types (`TTemplateParam::$defining_class`, `fn-…` for a function-like, and its name): a comparison meeting
 * a template finds its lower bound wherever the template was declared. The storages hold the lower bounds as declared
 * (by the declaration that wins, a stub over the code it overrides); this index holds them expanded in the scope of
 * their declaration, without the invalid ones, rebuilt each time the codebase is populated.
 *
 * @internal
 */
final class TypeTemplateLowerBounds
{
    /**
     * The valid lower bounds, expanded
     *
     * @var array<string, array<string, Union>>
     */
    private array $bounds = [];

    /**
     * The issue of each invalid lower bound, reported where the analysis meets its declaration
     *
     * @var array<string, array<string, CodeIssue>>
     */
    private array $issues = [];

    /**
     * The issues of the invalid lower bounds of stubs, which aren't analyzed, until they are reported
     *
     * @var list<CodeIssue>
     */
    private array $stub_issues = [];

    /**
     * @psalm-mutation-free
     */
    public function __construct(private readonly Codebase $codebase)
    {
    }

    /**
     * Parses the lower bounds of a declaration's templates, with the issues dropping the unusable ones: empty, invalid,
     * or naming a template of the declaration itself (`T super U`, `T super T`), which is not supported, nor is a lower
     * bound of a template whose upper bound names one. A lower bound may name the templates of the scope (a method's
     * class).
     *
     * @param array<string, array{type: string, line_number: int}> $entries
     * @param array<string, non-empty-array<string, Union>> $template_types the templates of the declaration
     * @param array<string, array<string, Union>> $scope_template_types the templates the lower bounds may name
     * @param array<string, TypeAlias> $type_aliases
     * @return array{array<string, Union>, list<InvalidDocblock>}
     */
    public static function parse(
        array $entries,
        array $template_types,
        array $scope_template_types,
        Aliases $aliases,
        array $type_aliases,
        ?string $self_fqcln,
        ?string $parent_fqcln,
        CodeLocation $location,
        string $owner,
        TypeScanner $type_scanner,
    ): array {
        $lower_bounds = [];
        $issues = [];

        foreach ($entries as $template_name => $entry) {
            $type_map = $template_types[$template_name] ?? null;

            if ($type_map === null) {
                continue;
            }

            $entry_location = $location->setCommentLine($entry['line_number']);

            if ($entry['type'] === '') {
                $issues[] = new InvalidDocblock(
                    'Missing lower bound after super for template ' . $template_name . ' in docblock for ' . $owner,
                    $entry_location,
                );

                continue;
            }

            try {
                $lower_bound = TemplateDefaults::parseTagType(
                    $entry['type'],
                    $aliases,
                    $scope_template_types,
                    $type_aliases,
                    $self_fqcln,
                    $parent_fqcln,
                    $type_scanner,
                );
            } catch (TypeParseTreeException $e) {
                $issues[] = new InvalidDocblock(
                    'Invalid lower bound of template ' . $template_name . ': ' . $e->getMessage()
                        . ' in docblock for ' . $owner,
                    $entry_location,
                );

                continue;
            }

            $defining_id = array_key_first($type_map);

            foreach ([$lower_bound, reset($type_map)] as $i => $bound) {
                $collector = new TemplateTypeCollector(true);
                $collector->traverseArray($bound->getAtomicTypes());

                foreach ($collector->getTemplateTypes() as $used_template) {
                    if ($used_template->defining_class === $defining_id) {
                        $issues[] = new InvalidDocblock(
                            ($i === 0
                                ? 'Lower bound of template ' . $template_name
                                : 'Upper bound of template ' . $template_name . ', which has a lower bound,')
                                . ' references template ' . $used_template->param_name
                                . ' of the same declaration, which is not supported, in docblock for ' . $owner,
                            $entry_location,
                        );

                        continue 3;
                    }
                }
            }

            $lower_bounds[$template_name] = $lower_bound->setFromDocblock();
        }

        return [$lower_bounds, $issues];
    }

    /**
     * Rebuilds the index from the storages, now that the codebase is populated (again, as what the lower bounds name
     * may have changed): expands each lower bound in the scope of its declaration, then leaves out the invalid ones,
     * with the issue to report for each
     */
    public function expand(): void
    {
        $this->bounds = [];
        $this->issues = [];
        $this->stub_issues = [];
        $declarations = [];

        foreach (ClassLikeStorageProvider::getAll() as $storage) {
            $self_class = $storage->is_trait ? null : $storage->name;

            if ($storage->type_template_lower_bounds !== []) {
                $declarations[$storage->name]
                    = [$storage, $storage->name, $self_class, $storage->parent_class, null, $storage->stubbed];
            }

            foreach ($storage->methods as $method_name_lc => $method_storage) {
                if ($method_storage->type_template_lower_bounds !== []) {
                    $declarations['fn-' . strtolower($storage->name) . '::' . $method_name_lc] = [
                        $method_storage,
                        $storage->name . '::' . $method_storage->cased_name,
                        $self_class,
                        $storage->parent_class,
                        $storage,
                        $storage->stubbed,
                    ];
                }
            }
        }

        $stubbed_functions = $this->codebase->functions->getAllStubbedFunctions();

        foreach (FileStorageProvider::getAll() as $file_storage) {
            foreach ($file_storage->functions as $function_id => $function_storage) {
                $stub = $stubbed_functions[$function_id] ?? null;

                // a stub declares the function wherever else it is declared
                if ($function_storage->type_template_lower_bounds !== []
                    && ($stub === null || $stub === $function_storage)
                ) {
                    $declarations['fn-' . $function_id] = [
                        $function_storage,
                        $function_storage->cased_name ?? $function_id,
                        null,
                        null,
                        null,
                        $stub !== null,
                    ];
                }
            }
        }

        // all the lower bounds first, as validating one compares it with the others
        foreach ($declarations as $defining_id => [$storage, , $self_class, $parent_class]) {
            foreach ($storage->type_template_lower_bounds as $template_name => $lower_bound) {
                $this->bounds[$defining_id][$template_name]
                    = TypeExpander::expandUnion($this->codebase, $lower_bound, $self_class, $self_class, $parent_class);
            }
        }

        foreach ($declarations as $defining_id => $declaration) {
            [$storage, $owner, $self_class, $parent_class, $class_storage, $in_stub] = $declaration;
            $expand = fn(Union $type): Union
                => TypeExpander::expandUnion($this->codebase, $type, $self_class, $self_class, $parent_class);

            foreach ($storage->type_template_lower_bounds as $template_name => $_) {
                $upper_bound = $storage->template_types[$template_name][$defining_id] ?? null;
                $default = $storage->template_defaults[$template_name] ?? null;
                $issue = $upper_bound === null || $storage->location === null
                    ? null
                    : $this->getIssue(
                        $this->bounds[$defining_id][$template_name],
                        $expand($upper_bound),
                        $default === null ? null : $expand($default),
                        $storage instanceof ClassLikeStorage ? $storage : $class_storage,
                        $storage instanceof ClassLikeStorage ? null : $class_storage,
                        $template_name,
                        $owner,
                        $storage->location,
                    );

                if ($issue === null) {
                    continue;
                }

                unset($this->bounds[$defining_id][$template_name]);

                if ($in_stub) {
                    $this->stub_issues[] = $issue;
                } else {
                    $this->issues[$defining_id][$template_name] = $issue;
                }
            }
        }
    }

    /**
     * Reports the invalid lower bounds of a declaration the analysis meets
     *
     * @param array<string, non-empty-array<string, Union>> $template_types the templates of the declaration
     * @param array<string> $suppressed_issues
     */
    public function report(array $template_types, array $suppressed_issues): void
    {
        foreach ($template_types as $template_name => $type_map) {
            $issue = $this->issues[array_key_first($type_map)][$template_name] ?? null;

            if ($issue !== null) {
                IssueBuffer::maybeAdd($issue, $suppressed_issues);
            }
        }
    }

    /**
     * Reports the invalid lower bounds of the stubs, once the codebase their names refer to is complete
     */
    public function reportStubIssues(): void
    {
        foreach ($this->stub_issues as $issue) {
            IssueBuffer::maybeAddStubIssue($issue);
        }

        $this->stub_issues = [];
    }

    /**
     * The lower bound of the template, if it has one
     *
     * @psalm-mutation-free
     */
    public function get(string $defining_id, string $template_name): ?Union
    {
        return $this->bounds[$defining_id][$template_name] ?? null;
    }

    /**
     * @psalm-mutation-free
     */
    public function isEmpty(): bool
    {
        return $this->bounds === [];
    }

    /**
     * Hack's rule, how a call or `new` infers a template with a lower bound: the lower bound is one more type the
     * template takes, so the template is the join of the lower bound and what the arguments give it (`keep(1)` is
     * `1|Cat`), or the lower bound alone when they give nothing. The join keeps both unless one contains the other (an
     * int is no float, a `list<int>` no `list<float>`). The type an invariant argument pins the template to (`Box<T>`
     * given a `Box<int>`), and each type restricting it from above, must contain the lower bound instead: reported at
     * the call.
     *
     * @param list<TemplateBound> $bounds what the call gives the template
     * @param list<TemplateBound> $upper_bounds what restricts it from above
     * @return ?Union what the call infers for the template, null to leave it to its bounds: those of a pinned
     *     template, or its default when it applies
     */
    public static function infer(
        StatementsAnalyzer $statements_analyzer,
        Union $lower_bound,
        array $bounds,
        array $upper_bounds,
        bool $default_applies,
        string $template_name,
        CodeLocation $code_location,
        ?string $function_id,
    ): ?Union {
        $codebase = $statements_analyzer->getCodebase();
        $argument_bounds = [];
        $pinned = false;

        foreach ($bounds as $bound) {
            if ($bound->equality_bound_classlike !== null) {
                $upper_bounds[] = $bound;
                $pinned = true;
            } elseif (!$bound->from_constraint_fallback) {
                $argument_bounds[] = $bound;
            } elseif ($bound->from_default) {
                // the default contains the lower bound, as checked where it is declared
                $default_applies = true;
            }
        }

        foreach ($upper_bounds as $upper_bound) {
            if (!self::checkUpperBound(
                $statements_analyzer,
                $upper_bound->type,
                $template_name,
                $lower_bound,
                $code_location,
                $function_id,
            )) {
                return null;
            }
        }

        if ($pinned) {
            return null;
        }

        if ($argument_bounds === []) {
            return $default_applies ? null : $lower_bound;
        }

        $inferred = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds($argument_bounds, $codebase);

        // a type variable the lower bound stands for (of a class created without type arguments) is compared only by
        // taking a bound, which these comparisons do not record: the join keeps both
        if (!array_any(
            $lower_bound->getAtomicTypes(),
            static fn(Atomic $atomic): bool => $atomic instanceof TTypeVariable,
        )) {
            if (LowerBoundProof::isContainedBy($codebase, $inferred, $lower_bound)) {
                return $lower_bound;
            }

            if (LowerBoundProof::isContainedBy($codebase, $lower_bound, $inferred)) {
                return $inferred;
            }
        }

        return Type::combineUnionTypes($inferred, $lower_bound, $codebase);
    }

    /**
     * A type restricting a template from above must contain its lower bound. A type variable it is (of a `Box`
     * created without type arguments) gets the lower bound as one more of its lower bounds, as a value the callee may
     * write to it.
     */
    private static function checkUpperBound(
        StatementsAnalyzer $statements_analyzer,
        Union $upper_bound,
        string $template_name,
        Union $lower_bound,
        CodeLocation $code_location,
        ?string $function_id,
    ): bool {
        $type_variable_bounds = [];

        foreach ($upper_bound->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TTypeVariable) {
                $type_variable_bounds[] = [$atomic_type->name, new TemplateBound($lower_bound)];
            }
        }

        if ($type_variable_bounds !== []) {
            $statements_analyzer->type_variable_tracker->addBounds(
                $type_variable_bounds,
                [],
                $code_location,
                $statements_analyzer->getSuppressedIssues(),
            );

            return true;
        }

        if (LowerBoundProof::isContainedBy($statements_analyzer->getCodebase(), $lower_bound, $upper_bound)) {
            return true;
        }

        IssueBuffer::maybeAdd(
            new InvalidArgument(
                'Incompatible types found for ' . $template_name . ' (' . $upper_bound->getId()
                    . ' does not contain its lower bound ' . $lower_bound->getId() . ')',
                $code_location,
                $function_id,
            ),
            $statements_analyzer->getSuppressedIssues(),
        );

        return false;
    }

    /**
     * The issue of an invalid lower bound, if it is: a class it is is undefined (in a trait, `self`, `static` and
     * `parent` are the class using it), it names a template of the class of its method the position does not allow,
     * it is outside the upper bound, or outside the default. The top level of the lower bound of a method template is
     * a covariant position, and a template nested in it must be invariant (its position is not worked out).
     */
    private function getIssue(
        Union $lower_bound,
        Union $upper_bound,
        ?Union $default,
        ?ClassLikeStorage $scope_storage,
        ?ClassLikeStorage $method_class_storage,
        string $template_name,
        string $owner,
        CodeLocation $location,
    ): ?CodeIssue {
        $nested_templates = new TemplateTypeCollector();
        $used_templates = [];

        foreach ($lower_bound->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TNamedObject
                && !($scope_storage?->is_trait
                    && in_array(strtolower($atomic_type->value), ['self', 'static', 'parent'], true))
                && !$this->codebase->classlikes->classOrInterfaceOrEnumExists($atomic_type->value)
            ) {
                return new UndefinedDocblockClass(
                    'Docblock-defined class, interface or enum named ' . $atomic_type->value . ' does not exist',
                    $location,
                    $atomic_type->value,
                );
            }

            if ($atomic_type instanceof TTemplateParam) {
                $used_templates[] = [$atomic_type, TemplateVariance::Covariant];
            } else {
                $nested_templates->traverse($atomic_type);
            }
        }

        foreach ($nested_templates->getTemplateTypes() as $used_template) {
            $used_templates[] = [$used_template, TemplateVariance::Invariant];
        }

        $class_template_names = array_keys($method_class_storage->template_types ?? []);

        foreach ($used_templates as [$used_template, $allowed_variance]) {
            $offset = $used_template->defining_class === $method_class_storage?->name
                ? array_search($used_template->param_name, $class_template_names, true)
                : false;
            $variance = $offset === false ? null : $method_class_storage?->getTemplateVariance($offset);

            if ($variance !== null && $variance !== TemplateVariance::Invariant && $variance !== $allowed_variance) {
                return new InvalidTemplateParam(
                    'Template param ' . $used_template->param_name . ' of ' . $used_template->defining_class
                        . ' is marked ' . strtolower($variance->name) . ' and cannot be used in the lower bound of'
                        . ' template ' . $template_name . ' of ' . $owner,
                    $location,
                );
            }
        }

        if (!$upper_bound->isMixed() && !LowerBoundProof::isContainedBy($this->codebase, $lower_bound, $upper_bound)) {
            return new InvalidDocblock(
                'The lower bound ' . $lower_bound->getId() . ' of template ' . $template_name
                    . ' is not within its bound ' . $upper_bound->getId() . ' in docblock for ' . $owner,
                $location,
            );
        }

        if ($default !== null && !LowerBoundProof::isContainedBy($this->codebase, $lower_bound, $default)) {
            return new InvalidDocblock(
                'The default ' . $default->getId() . ' of template ' . $template_name
                    . ' is not a supertype of its lower bound ' . $lower_bound->getId() . ' in docblock for ' . $owner,
                $location,
            );
        }

        return null;
    }
}
