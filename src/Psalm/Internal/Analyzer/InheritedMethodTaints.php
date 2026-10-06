<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use InvalidArgumentException;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\MethodIdentifier;
use Psalm\IssueBuffer;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function array_key_exists;
use function spl_object_id;
use function strtolower;

/**
 * The taints of an inherited method run on an object of a class inheriting it: its body is analyzed again for that
 * class, with nodes of its own, so that what the method sets on the properties of the object, and what it returns
 * of them, is that class's, rather than shared by every class inheriting it.
 *
 * @internal
 */
final class InheritedMethodTaints
{
    /** The class of the object a call of an inherited method (parent::, self::, A::) runs on, set on its arguments */
    public const BODY_CLASS_ATTRIBUTE = 'inheritedMethodBodyClass';

    /** @var array<string, string|null> class (lowercase) and declaring method id => suffix of its nodes, if any */
    private static array $bodies = [];

    /** The graph the bodies analyzed are in */
    private static ?int $graph_id = null;

    /**
     * The suffix of the nodes of the body of $declaring_method_id analyzed for objects of $class, if it is
     * (analyzing it now if needed), or null: calls go to the method itself.
     */
    public static function getBodySuffix(
        StatementsAnalyzer $statements_analyzer,
        string $class,
        MethodIdentifier $declaring_method_id,
    ): ?string {
        $codebase = $statements_analyzer->getCodebase();

        if ($codebase->taint_flow_graph === null
            || strtolower($class) === $declaring_method_id->fq_class_name
            || !$codebase->classlike_storage_provider->has($class)
            || !$codebase->classlike_storage_provider->has($declaring_method_id->fq_class_name)
        ) {
            return null;
        }

        if (self::$graph_id !== spl_object_id($codebase->taint_flow_graph)) {
            self::$graph_id = spl_object_id($codebase->taint_flow_graph);
            self::$bodies = [];
        }

        $class_storage = $codebase->classlike_storage_provider->get($class);
        $key = strtolower($class_storage->name) . ' ' . (string) $declaring_method_id;

        if (array_key_exists($key, self::$bodies)) {
            return self::$bodies[$key];
        }

        self::$bodies[$key] = null;

        try {
            $method_storage = $codebase->methods->getStorage($declaring_method_id);
        } catch (InvalidArgumentException) {
            return null;
        }

        $declaring_class_storage = $codebase->classlike_storage_provider->get($declaring_method_id->fq_class_name);

        // the taints of an object of a class specialized per instance already travel with the object
        if ($method_storage->is_static
            || $declaring_class_storage->specialize_instance
            || $class_storage->specialize_instance
            || $method_storage->abstract
            || $method_storage->location === null
            || !$declaring_class_storage->user_defined
            || $declaring_class_storage->is_trait
            || $declaring_class_storage->is_interface
            || $class_storage->is_trait
            || $class_storage->is_interface
            || !$codebase->config->isInProjectDirs($method_storage->location->file_path)
            || !$codebase->classExtends($class_storage->name, $declaring_class_storage->name)
        ) {
            return null;
        }

        $suffix = ' for ' . $class_storage->name;
        self::$bodies[$key] = $suffix;

        self::analyzeBody(
            $statements_analyzer,
            $class_storage->name,
            $declaring_class_storage->name,
            $declaring_method_id,
            $suffix,
        );

        return $suffix;
    }

    private static function analyzeBody(
        StatementsAnalyzer $statements_analyzer,
        string $class,
        string $declaring_class,
        MethodIdentifier $declaring_method_id,
        string $suffix,
    ): void {
        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        $file_analyzer = $project_analyzer->getFileAnalyzerForClassLike($declaring_class);
        $file_analyzer->setRootFilePath(
            $statements_analyzer->getRootFilePath(),
            $statements_analyzer->getRootFileName(),
        );
        $file_analyzer->populateCheckers($codebase->getStatementsForFile($file_analyzer->getFilePath()));

        $class_analyzer = $file_analyzer->class_analyzers_to_analyze[strtolower($declaring_class)] ?? null;

        if ($class_analyzer === null) {
            return;
        }

        // the body of the declaring class, run on an object of $class
        $context = new Context($declaring_class);
        $context->vars_in_scope['$this'] = new Union([new TNamedObject($class)]);
        // not the scope of an analysis collecting mutations, which may be skipped (see FunctionLikeAnalyzer)
        $context->vars_in_scope['$__inherited_body_for'] = new Union([new TNamedObject($class)]);

        $old_body_suffix = DataFlowNode::$body_suffix;
        $method_id_lc = strtolower((string) $declaring_method_id);
        $old_method_suffix = DataFlowNode::$method_suffixes[$method_id_lc] ?? null;

        DataFlowNode::$body_suffix = $suffix;
        DataFlowNode::$method_suffixes[$method_id_lc] = $suffix;
        IssueBuffer::startRecording();

        try {
            $class_analyzer->analyzeMethodForInheritingClass($declaring_method_id->method_name, $context);
        } finally {
            IssueBuffer::clearRecordingLevel();
            IssueBuffer::stopRecording();
            DataFlowNode::$body_suffix = $old_body_suffix;

            if ($old_method_suffix === null) {
                unset(DataFlowNode::$method_suffixes[$method_id_lc]);
            } else {
                DataFlowNode::$method_suffixes[$method_id_lc] = $old_method_suffix;
            }

            $file_analyzer->class_analyzers_to_analyze = [];
            $file_analyzer->interface_analyzers_to_analyze = [];
            $file_analyzer->clearSourceBeforeDestruction();
        }
    }

    /**
     * The property `$this->$prop_name` of the body of an inherited method analyzed for a class inheriting it: the
     * private property of the class declaring the method is that class's, others are those of the object's class
     */
    public static function getPropertyIdInBody(
        Codebase $codebase,
        ?Context $context,
        PropertyFetch $stmt,
        string $property_id,
    ): string {
        if (DataFlowNode::$body_suffix === null
            || $context === null
            || $context->self === null
            || !$stmt->var instanceof Variable
            || $stmt->var->name !== 'this'
            || !$stmt->name instanceof Identifier
            || !self::isPrivateIn($codebase, $context->self, $stmt->name->name)
        ) {
            return $property_id;
        }

        return $codebase->classlike_storage_provider->get($context->self)->name . '::$' . $stmt->name->name;
    }

    /**
     * Whether $class declares property $prop_name private
     */
    public static function isPrivateIn(Codebase $codebase, string $class, string $prop_name): bool
    {
        if (!$codebase->classlike_storage_provider->has($class)) {
            return false;
        }

        $property = $codebase->classlike_storage_provider->get($class)->properties[$prop_name] ?? null;

        return $property !== null && $property->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE;
    }

    /**
     * Creates nodes of the parameters and return value of $method_id with $suffix, the nodes of its body analyzed for
     * a class inheriting it
     *
     * @template T
     * @param callable(): T $create
     * @return T
     */
    public static function withMethodSuffix(MethodIdentifier $method_id, ?string $suffix, callable $create): mixed
    {
        if ($suffix === null) {
            return $create();
        }

        $method_id_lc = strtolower((string) $method_id);
        $old = DataFlowNode::$method_suffixes[$method_id_lc] ?? null;
        DataFlowNode::$method_suffixes[$method_id_lc] = $suffix;

        try {
            return $create();
        } finally {
            if ($old === null) {
                unset(DataFlowNode::$method_suffixes[$method_id_lc]);
            } else {
                DataFlowNode::$method_suffixes[$method_id_lc] = $old;
            }
        }
    }
}
