<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use Override;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\PhpVisitor\ShortClosureVisitor;
use Psalm\Internal\TypeVisitor\CapturedValueVisitor;
use Psalm\Issue\DuplicateParam;
use Psalm\Issue\ImpureByReferenceAssignment;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\UndefinedVariable;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\Capabilities;
use Psalm\Storage\FunctionStorage;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

use function in_array;
use function is_string;
use function preg_match;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

/**
 * @internal
 * @extends FunctionLikeAnalyzer<PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction>
 */
final class ClosureAnalyzer extends FunctionLikeAnalyzer
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction $function
     * @param ?string $bound_this_class class `$this` is bound to by `@param-closure-this` at the call site
     */
    public function __construct(
        PhpParser\Node\FunctionLike $function,
        SourceAnalyzer $source,
        private readonly ?string $bound_this_class = null,
    ) {
        $codebase = $source->getCodebase();

        $function_id = strtolower($source->getFilePath())
            . ':' . $function->getLine()
            . ':' . (int)$function->getAttribute('startFilePos')
            . ':-:closure';

        $this->closure_id = $function_id;

        $storage = $codebase->getClosureStorage($source->getFilePath(), $function_id);

        parent::__construct($function, $source, $storage);
    }

    /** @var lowercase-string */
    private readonly string $closure_id;

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function getMutationNodeId(): string
    {
        return CodeUseGraph::functionLikeNode($this->closure_id);
    }

    /**
     * The variable this closure is assigned to and captures by reference, if
     * any: calls through it from the closure body are recursive calls.
     */
    /**
     * The variables captured by reference that the closure body writes.
     *
     * @var array<string, true>
     */
    public array $captured_by_ref_writes = [];

    /**
     * The types of the variables captured by reference where the closure body returns.
     *
     * @var array<string, list<Union>>
     */
    public array $captured_by_ref_types_at_return = [];

    /**
     * The variables captured by reference whose literals the closure body sees widened (and their offsets and
     * properties), with the types they have in the enclosing scope: what the code after the closure is made of, as the
     * body may leave them untouched.
     *
     * @var array<string, Union>
     */
    public array $captured_by_ref_enclosing_types = [];

    /**
     * `A::$property::get` when this is the body of a property hook, see ClassAnalyzer::analyzePropertyHooks()
     */
    public function getPropertyHookId(): ?string
    {
        /** @var mixed $hook_id */
        $hook_id = $this->function->getAttribute(ClassAnalyzer::PROPERTY_HOOK_ATTRIBUTE);

        return is_string($hook_id) ? $hook_id : null;
    }

    public function getRecursiveVarId(): ?string
    {
        /** @var mixed $var_id */
        $var_id = $this->function->getAttribute('recursive_var_id');

        return is_string($var_id) ? $var_id : null;
    }

    /** @psalm-mutation-free */
    #[Override]
    public function getFQCLN(): ?string
    {
        return $this->bound_this_class ?? parent::getFQCLN();
    }

    /** @psalm-mutation-free */
    #[Override]
    public function getParentFQCLN(): ?string
    {
        if ($this->bound_this_class === null) {
            return parent::getParentFQCLN();
        }

        return $this->codebase->classlike_storage_provider->get($this->bound_this_class)->parent_class;
    }

    /** @psalm-mutation-free */
    #[Override]
    public function getTemplateTypeMap(): ?array
    {
        return $this->source->getTemplateTypeMap();
    }

    /**
     * @return non-empty-lowercase-string
     */
    public function getClosureId(): string
    {
        return strtolower($this->getFilePath())
            . ':' . $this->function->getLine()
            . ':' . (int)$this->function->getAttribute('startFilePos')
            . ':-:closure';
    }

    /**
     * The id of the function-like the source's code is in, or '' outside of any: the scope of its variables
     */
    public static function getScopeId(StatementsSource $source): string
    {
        while ($source instanceof StatementsAnalyzer) {
            $source = $source->getSource();
        }

        return $source instanceof FunctionLikeAnalyzer ? $source->getId() : '';
    }

    /**
     * The ids of the scopes (see getScopeId()) whose variable each name is, for a closure made in the source that
     * captures them by reference: a closure the source is in shares a variable it captured by reference with the
     * scope it was made in
     *
     * @param array<string, bool> $byref_uses
     * @return array<string, non-empty-list<string>>
     */
    public static function getByRefUseScopes(StatementsSource $source, array $byref_uses): array
    {
        $scopes = [];

        foreach ($byref_uses as $use_name => $_) {
            $scope = $source;

            while (true) {
                while ($scope instanceof StatementsAnalyzer) {
                    $scope = $scope->getSource();
                }

                if (!$scope instanceof FunctionLikeAnalyzer) {
                    $scopes[$use_name][] = '';
                    break;
                }

                $scopes[$use_name][] = $scope->getId();
                if (!$scope instanceof self
                    || !$scope->storage instanceof FunctionStorage
                    || !isset($scope->storage->byref_uses[$use_name])
                ) {
                    break;
                }

                $scope = $scope->getSource();
            }
        }

        return $scopes;
    }

    /**
     * The variables of the current scope that calling the analysed callable may write, also recorded for
     * {@see \Psalm\Internal\Algebra\FormulaGenerator}. What a closure captured by reference are variables of the
     * scopes it was made in (see getByRefUseScopes()): elsewhere, a variable of the same name is taken to be one when
     * this scope binds it by reference to a variable outside (`use (&$x)`, `global $x`, a by-reference parameter or
     * foreach value, ...)
     *
     * @return array<string, true>
     */
    public static function analyzeVarIdsWrittenByCall(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        PhpParser\Node\Expr $callable,
    ): array {
        $var_ids = [];
        $scope_id = null;

        foreach ($statements_analyzer->node_data->getType($callable)?->getAtomicTypes() ?? [] as $closure) {
            if (!$closure instanceof TClosure || !$closure->byref_uses) {
                continue;
            }

            $scope_id ??= self::getScopeId($statements_analyzer);

            foreach ($closure->byref_uses as $use_name => $_) {
                $var_id = '$' . $use_name;

                if (!isset($closure->byref_use_scopes[$use_name])
                    || in_array($scope_id, $closure->byref_use_scopes[$use_name], true)
                    || isset($context->references_to_external_scope[$var_id])
                ) {
                    $var_ids[$var_id] = true;
                }
            }
        }

        $statements_analyzer->node_data->setVarIdsWrittenByCall($callable, $var_ids);

        return $var_ids;
    }

    /**
     * Type `@param-closure-this` bound `$this` to at this call site, as stamped on the node by
     * {@see \Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentsAnalyzer}.
     *
     * Only a single known class binds. A union would leave class scope ambiguous, and a static
     * closure cannot be rebound at all, so both fall back to the usual unbound handling.
     *
     * @param PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction $stmt
     */
    private static function resolveBoundThis(
        Codebase $codebase,
        PhpParser\Node\FunctionLike $stmt,
    ): ?TNamedObject {
        /** @psalm-suppress MixedAssignment attribute values are untyped */
        $bound_this_type = $stmt->getAttribute('psalm-closure-this-type');

        if ($stmt->static
            || !$bound_this_type instanceof Union
            || !$bound_this_type->isSingle()
        ) {
            return null;
        }

        $bound_atomic = $bound_this_type->getSingleAtomic();

        if (!$bound_atomic instanceof TNamedObject
            || !$codebase->classlike_storage_provider->has($bound_atomic->value)
        ) {
            return null;
        }

        return $bound_atomic;
    }

    /**
     * The enclosing scope can change a captured reference after the closure is created, so the value it held
     * at that point says nothing about the value the closure sees.
     *
     * @psalm-pure
     */
    private static function widenLiteralsOfByRefCapture(Union $type): Union
    {
        $widened = [];
        $changed = false;

        foreach ($type->getAtomicTypes() as $original) {
            $atomic = $original;

            if ($atomic instanceof TLiteralClassString) {
                // a class-string stays one
            } elseif ($atomic instanceof TLiteralInt) {
                $atomic = new TInt($atomic->from_docblock);
            } elseif ($atomic instanceof TLiteralFloat) {
                $atomic = new TFloat($atomic->from_docblock);
            } elseif ($atomic instanceof TLiteralString) {
                $atomic = new TString($atomic->from_docblock);
            } elseif ($atomic instanceof TTrue || $atomic instanceof TFalse) {
                $atomic = new TBool($atomic->from_docblock);
            } elseif ($atomic instanceof TEnumCase) {
                $atomic = new TNamedObject($atomic->value, false, false, [], $atomic->from_docblock);
            } elseif ($atomic instanceof TKeyedArray) {
                $properties = [];
                $properties_changed = false;

                foreach ($atomic->properties as $key => $property) {
                    $properties[$key] = self::widenLiteralsOfByRefCapture($property);
                    $properties_changed = $properties_changed || $properties[$key] !== $property;
                }

                $fallback_params = $atomic->fallback_params;

                if ($fallback_params !== null) {
                    $fallback_value = self::widenLiteralsOfByRefCapture($fallback_params[1]);

                    if ($fallback_value !== $fallback_params[1]) {
                        $fallback_params = [$fallback_params[0], $fallback_value];
                        $properties_changed = true;
                    }
                }

                if ($properties_changed) {
                    $atomic = TKeyedArray::make(
                        $properties,
                        $atomic->class_strings,
                        $fallback_params,
                        $atomic->is_list,
                        $atomic->from_docblock,
                    );
                }
            } elseif ($atomic instanceof TArray) {
                $atomic = $atomic->setTypeParams([
                    $atomic->type_params[0],
                    self::widenLiteralsOfByRefCapture($atomic->type_params[1]),
                ]);
            }

            $changed = $changed || $atomic !== $original;
            $widened[$atomic->getKey()] = $atomic;
        }

        return $changed ? $type->setTypes($widened) : $type;
    }

    /**
     * `use ($a)` binds `$a` in the closure, to null when it is undefined
     *
     * @psalm-capabilities read-props
     */
    private static function getBoundUseType(Union $type): Union
    {
        if (!$type->possibly_undefined && !$type->possibly_undefined_from_try) {
            return $type;
        }

        $type = $type->setPossiblyUndefined(false, false);

        if ($type->isNullable()) {
            return $type;
        }

        foreach ($type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TNonEmptyMixed) {
                return $type->getBuilder()
                    ->addType(new TMixed($atomic->from_loop_isset, $atomic->from_docblock))
                    ->freeze();
            }

            if ($atomic instanceof TMixed) {
                return $type;
            }
        }

        return $type->getBuilder()->addType(new TNull())->freeze();
    }

    /**
     * The type of a variable captured by a closure, as seen in its body. The value is shared with
     * the enclosing scope, so an object the enclosing scope created is not fresh in the closure:
     * the closure may be called more than once, and writing that object writes state that outlives
     * the call. Only the enclosing scope and the closure hold it, though, so it is state of the
     * closure, as the objects a class instance creates are its own. The same holds for the objects
     * in a captured array, for a capture by reference, and for a closure capturing state of the
     * closure it is created in.
     */
    private static function getCapturedType(Union $type): Union
    {
        (new CapturedValueVisitor())->traverse($type);

        return $type;
    }

    /**
     * @param PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction $stmt
     */
    public static function analyzeExpression(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\FunctionLike $stmt,
        Context $context,
    ): bool {
        if ($stmt instanceof PhpParser\Node\Expr\Closure
            && self::analyzeClosureUses($statements_analyzer, $stmt, $context) === false
        ) {
            return false;
        }

        $codebase = $statements_analyzer->getCodebase();

        $bound_atomic = self::resolveBoundThis($codebase, $stmt);
        $bound_self = $bound_atomic?->value;

        $closure_analyzer = new ClosureAnalyzer($stmt, $statements_analyzer, $bound_self);

        $use_context = new Context($bound_self ?? $context->self);

        if ($bound_atomic !== null) {
            $use_context->vars_in_scope['$this'] = new Union([$bound_atomic]);
        } elseif (!$statements_analyzer->isStatic() && !$closure_analyzer->isStatic()) {
            if ($context->self) {
                $outer_this_atomic = isset($context->vars_in_scope['$this'])
                    && $context->vars_in_scope['$this']->isSingle()
                    ? $context->vars_in_scope['$this']->getSingleAtomic()
                    : null;

                // keep the template params of the class, so that its methods take the closure's TNode for theirs
                $this_atomic = $outer_this_atomic instanceof TGenericObject
                    && strtolower($outer_this_atomic->value) === strtolower($context->self)
                    ? $outer_this_atomic
                    : new TNamedObject($context->self, true);

                $use_context->vars_in_scope['$this'] = new Union([$this_atomic]);
            }
        }

        if ($bound_self === null) {
            foreach ($context->vars_in_scope as $var => $type) {
                if (str_starts_with($var, '$this->')) {
                    $use_context->vars_in_scope[$var] = $type;
                }
            }
        }

        if ($bound_self === null
            && $context->self !== null
            && $codebase->classlike_storage_provider->has($context->self)
        ) {
            $self_class_storage = $codebase->classlike_storage_provider->get($context->self);

            ClassAnalyzer::addContextProperties(
                $statements_analyzer,
                $self_class_storage,
                $use_context,
                $context->self,
                $statements_analyzer->getParentFQCLN(),
            );
        }

        if ($bound_self === null) {
            foreach ($context->vars_possibly_in_scope as $var => $_) {
                if (str_starts_with($var, '$this->')) {
                    $use_context->vars_possibly_in_scope[$var] = true;
                }
            }
        }

        $was_by_ref = [];

        if ($stmt instanceof PhpParser\Node\Expr\Closure) {
            foreach ($stmt->uses as $use) {
                if (!is_string($use->var->name)) {
                    continue;
                }

                $use_var_id = '$' . $use->var->name;

                if ($statements_analyzer->data_flow_graph
                    && $context->hasVariable($use_var_id)
                ) {
                    $parent_nodes = $context->vars_in_scope[$use_var_id]->parent_nodes;

                    foreach ($parent_nodes as $parent_node) {
                        $statements_analyzer->data_flow_graph->addPath(
                            $parent_node,
                            DataFlowNode::getForClosureUse(),
                            'closure-use',
                        );
                    }
                }

                $use_context->vars_in_scope[$use_var_id] =
                    $context->hasVariable($use_var_id)
                    ? self::getCapturedType(self::getBoundUseType($context->vars_in_scope[$use_var_id]))
                    : Type::getMixed();

                if ($use->byRef) {
                    $was_by_ref[$use_var_id] = $context->hasVariable($use_var_id)
                        && $context->vars_in_scope[$use_var_id]->by_ref;

                    $captured_type = $use_context->vars_in_scope[$use_var_id];
                    $use_context->vars_in_scope[$use_var_id] = self::widenLiteralsOfByRefCapture(
                        $captured_type,
                    )->setProperties(['by_ref' => true]);

                    if ($use_context->vars_in_scope[$use_var_id]->getId() !== $captured_type->getId()) {
                        $closure_analyzer->captured_by_ref_enclosing_types[$use_var_id] = $captured_type;
                    }
                    $use_context->references_to_external_scope[$use_var_id] = true;

                    if (isset($context->bound_globals[$use_var_id])) {
                        $use_context->bound_globals[$use_var_id] = $context->bound_globals[$use_var_id];
                    }

                    // shared with the enclosing scope, plus what that scope needs to write it
                    $use_context->captured_by_ref[$use_var_id]
                        = AssignmentAnalyzer::getExternalWriteCapabilities($context, $use_var_id);
                }

                $use_context->vars_possibly_in_scope[$use_var_id] = true;

                foreach ($context->vars_in_scope as $var_id => $type) {
                    if (preg_match('/^\$' . $use->var->name . '[\[\-]/', $var_id)) {
                        // the enclosing scope can change what a variable captured by reference holds after the
                        // closure is created, so can it its offsets and properties: their literals are widened too
                        $captured_type = self::getCapturedType($type);
                        $use_context->vars_in_scope[$var_id] = $use->byRef
                            ? self::widenLiteralsOfByRefCapture($captured_type)
                            : $captured_type;

                        if ($use->byRef && $use_context->vars_in_scope[$var_id]->getId() !== $captured_type->getId()) {
                            $closure_analyzer->captured_by_ref_enclosing_types[$var_id] = $captured_type;
                        }
                        $use_context->vars_possibly_in_scope[$var_id] = true;
                    }
                }
            }
        } else {
            $traverser = new PhpParser\NodeTraverser;

            $short_closure_visitor = new ShortClosureVisitor();

            $traverser->addVisitor($short_closure_visitor);
            $traverser->traverse($stmt->getStmts());

            foreach ($short_closure_visitor->getUsedVariables() as $use_var_id => $_) {
                if ($context->hasVariable($use_var_id)) {
                    $use_context->vars_in_scope[$use_var_id] = self::getCapturedType(
                        $context->vars_in_scope[$use_var_id],
                    );

                    // what is known of a readonly property holds in the closure; a deeper path goes through a
                    // value that may change
                    foreach ($context->vars_in_scope as $var_id => $type) {
                        if (str_starts_with($var_id, $use_var_id . '->')
                            && self::isReadonlyPropertyOf(
                                $statements_analyzer->getCodebase(),
                                $context->vars_in_scope[$use_var_id],
                                substr($var_id, strlen($use_var_id) + 2),
                            )
                        ) {
                            $use_context->vars_in_scope[$var_id] = self::getCapturedType($type);
                            $use_context->vars_possibly_in_scope[$var_id] = true;
                        }
                    }

                    if ($statements_analyzer->data_flow_graph) {
                        $parent_nodes = $context->vars_in_scope[$use_var_id]->parent_nodes;

                        foreach ($parent_nodes as $parent_node) {
                            $statements_analyzer->data_flow_graph->addPath(
                                $parent_node,
                                DataFlowNode::getForClosureUse(),
                                'closure-use',
                            );
                        }
                    }
                }

                $use_context->vars_possibly_in_scope[$use_var_id] = true;
            }
        }

        // a closure runs on the PHP version and in the SAPI of the code creating it
        foreach ([
            Context::PHP_VERSION_ID_VAR_ID,
            Context::PHP_SYMBOL_GUARD_VAR_ID,
            Context::PHP_SAPI_VAR_ID,
        ] as $var_id) {
            if (isset($context->vars_in_scope[$var_id])) {
                $use_context->vars_in_scope[$var_id] = $context->vars_in_scope[$var_id];
            }
        }

        $use_context->calling_method_id = $context->calling_method_id;
        $use_context->calling_function_id = $context->calling_function_id;
        $use_context->phantom_classes = $context->phantom_classes;

        $byref_vars = [];
        $closure_analyzer->analyze($use_context, $statements_analyzer->node_data, $context, false, $byref_vars);

        foreach ($byref_vars as $key => $value) {
            // the variable is shared with the closure, but is still this scope's own unless it
            // was a reference already
            $context->vars_in_scope[$key] = $value->setByRef($was_by_ref[$key] ?? false);

            // the closure may change it whenever it is called
            $context->removeVarFromConflictingClauses($key, $context->vars_in_scope[$key], $statements_analyzer);
        }

        // the closure writes variables it shares with this scope: when such a variable is
        // itself shared with somewhere else, this scope is what lets the closure write there
        foreach ($closure_analyzer->captured_by_ref_writes as $var_id => $_) {
            $required = $use_context->captured_by_ref[$var_id] ?? Capabilities::NONE;

            if ($required === Capabilities::NONE) {
                continue;
            }

            // in an enclosing closure, writing a variable that closure captured by reference too
            $enclosing_closure = $statements_analyzer->getSource();
            $enclosing_closure = $enclosing_closure instanceof self && isset($context->captured_by_ref[$var_id])
                ? $enclosing_closure
                : null;

            if ($enclosing_closure !== null) {
                $enclosing_closure->signalling_by_ref_capture = true;
            }

            $statements_analyzer->signalMutation(
                $required,
                $context,
                'variable ' . $var_id . ' captured by reference, written by the closure,',
                ImpureByReferenceAssignment::class,
                $stmt,
            );

            if ($enclosing_closure !== null) {
                $enclosing_closure->signalling_by_ref_capture = false;
            }
        }
        
        // creating a closure is not an effect: its capabilities are carried by its type and are
        // required where it is called or passed. Only the purity inference of the enclosing
        // function-like still follows the closure's final level, as if it were called.
        $statements_analyzer->signalMutationOnlyInferred(
            Capabilities::NONE,
            $closure_analyzer->storage,
            false,
            $closure_analyzer->getMutationNodeId(),
        );

        if (!$statements_analyzer->node_data->getType($stmt)) {
            $statements_analyzer->node_data->setType($stmt, Type::getClosure());
        }

        return true;
    }

    /**
     * Whether every class the type can be has the readonly property of that name
     *
     * @psalm-capabilities read-props
     */
    private static function isReadonlyPropertyOf(Codebase $codebase, Union $type, string $property_name): bool
    {
        if (!preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $property_name)) {
            return false;
        }

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof TNamedObject) {
                return false;
            }

            $property_id = $atomic_type->value . '::$' . $property_name;

            if (!$codebase->classlike_storage_provider->has($atomic_type->value)
                || !$codebase->properties->hasStorage($property_id)
                || !$codebase->properties->getStorage($property_id)->readonly
                // a private method of the class may still write it
                || $codebase->properties->getStorage($property_id)->allow_private_mutation
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return  false|null
     */
    private static function analyzeClosureUses(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Closure $stmt,
        Context $context,
    ): ?bool {
        $param_names = [];

        foreach ($stmt->params as $i => $param) {
            if ($param->var instanceof PhpParser\Node\Expr\Variable && is_string($param->var->name)) {
                $param_names[$i] = $param->var->name;
            } else {
                $param_names[$i] = '';
            }
        }

        foreach ($stmt->uses as $use) {
            if (!is_string($use->var->name)) {
                continue;
            }

            $use_var_id = '$' . $use->var->name;

            if (in_array($use->var->name, $param_names)) {
                if (IssueBuffer::accepts(
                    new DuplicateParam(
                        'Closure use duplicates param name ' . $use_var_id,
                        new CodeLocation($statements_analyzer->getSource(), $use->var),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                )) {
                    return false;
                }
            }

            if ($use->byRef) {
                $statements_analyzer->markCapturedByReference($use_var_id);
            }

            // capturing by reference defines the variable (as null) in the enclosing scope
            if ($use->byRef && !$context->hasVariable($use_var_id)) {
                continue;
            }

            if (!$context->hasVariable($use_var_id)) {
                if ($use_var_id === '$argv' || $use_var_id === '$argc') {
                    continue;
                }

                if (!isset($context->vars_possibly_in_scope[$use_var_id])) {
                    if ($context->check_variables) {
                        IssueBuffer::maybeAdd(
                            new UndefinedVariable(
                                'Cannot find referenced variable ' . $use_var_id,
                                new CodeLocation($statements_analyzer->getSource(), $use->var),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );

                        continue;
                    }
                }

                $first_appearance = $statements_analyzer->getFirstAppearance($use_var_id);

                if ($first_appearance) {
                    IssueBuffer::maybeAdd(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $use_var_id . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_analyzer->getSource(), $use->var),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );

                    continue;
                }

                if ($context->check_variables) {
                    IssueBuffer::maybeAdd(
                        new UndefinedVariable(
                            'Cannot find referenced variable ' . $use_var_id,
                            new CodeLocation($statements_analyzer->getSource(), $use->var),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            } elseif (!$use->byRef) {
                $use_type = $context->vars_in_scope[$use_var_id];

                if ($use_type->possibly_undefined_from_try) {
                    IssueBuffer::maybeAdd(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $use_var_id . ' defined in try block',
                            new CodeLocation($statements_analyzer->getSource(), $use->var),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } elseif ($use_type->possibly_undefined) {
                    // extract() defines variables without a first appearance
                    $first_appearance = $statements_analyzer->getFirstAppearance($use_var_id);

                    IssueBuffer::maybeAdd(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $use_var_id . ($first_appearance
                                ? ', first seen on line ' . $first_appearance->getLineNumber()
                                : ''),
                            new CodeLocation($statements_analyzer->getSource(), $use->var),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            }
        }

        return null;
    }
}
