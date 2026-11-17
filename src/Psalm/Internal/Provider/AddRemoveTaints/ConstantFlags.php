<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\AddRemoveTaints;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\BinaryOp\BitwiseAnd;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Eval_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Expr\PostInc;
use PhpParser\Node\Expr\PreDec;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\StaticVar;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Global_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Static_;
use PhpParser\Node\Stmt\Unset_;
use PhpParser\NodeFinder;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Storage\ClassLikeStorage;
use ReflectionClass;

use function array_map;
use function array_values;
use function class_exists;
use function count;
use function get_defined_constants;
use function in_array;
use function interface_exists;
use function is_array;
use function is_int;
use function is_string;
use function strtolower;

/**
 * The bits of the flags of a call, which tell the escapes it does. Only a compile-time constant expression tells them:
 * an int literal, a builtin constant (no code can redefine one), a class constant whose declared value is such an
 * expression, a variable of a function-like only ever assigned such expressions, and `|`, `&` and ternaries of them.
 * Anything else (a property, a parameter, a call) may hold any bits whatever its inferred type, which an assert()
 * compiled out in production, a `@var`, a `@param` with a suppressed or baselined wrong caller or a `$$name` write can
 * make a literal the runtime value doesn't have.
 *
 * So a taint is only removed for the bits a constant expression has, while the taints given back by the bits a value
 * may have go by its inferred type: there, an unknown type gives back more.
 *
 * @internal
 */
final class ConstantFlags
{
    /** The bits of a value that isn't constant: it has none for sure, and may have any */
    private const UNKNOWN = [0, -1];

    /** The builtin functions taking flags, which take all of their arguments by value */
    private const FLAG_FUNCTIONS = [
        'html_entity_decode',
        'htmlentities',
        'htmlspecialchars',
        'htmlspecialchars_decode',
        'json_decode',
        'json_encode',
    ];

    /**
     * The bits every value of the expression has, and those its values may have
     *
     * @return array{int, int}
     */
    public static function getBits(StatementsAnalyzer $statements_analyzer, Expr $expr): array
    {
        $codebase = $statements_analyzer->getCodebase();

        // self:: is the class of a method only: a closure may be bound to another scope, and the self:: of a trait
        // method is each class using it
        $source = $statements_analyzer->getSource();
        $class = $source instanceof MethodAnalyzer && !$source->getSource() instanceof TraitAnalyzer
            ? $source->getFQCLN()
            : null;
        $self = $class !== null ? self::getClass($codebase, $class) : null;

        return self::getExpressionBits($codebase, $expr, $self, true, [], $statements_analyzer);
    }

    /**
     * @param bool $static_is_self whether `static::` may be the class of $self (it can't be in the value of a constant)
     * @param array<string, true> $visited the class constants and variables being evaluated, against cycles
     * @param ?StatementsAnalyzer $statements_analyzer the analyzer of the code the variables are read in, if any
     * @return array{int, int}
     */
    private static function getExpressionBits(
        Codebase $codebase,
        Expr $expr,
        ?ClassLikeStorage $self,
        bool $static_is_self,
        array $visited,
        ?StatementsAnalyzer $statements_analyzer = null,
    ): array {
        if ($expr instanceof Int_) {
            return [$expr->value, $expr->value];
        }

        if ($expr instanceof ConstFetch) {
            $value = self::getBuiltinConstant($expr->name);

            return $value !== null ? [$value, $value] : self::UNKNOWN;
        }

        if ($expr instanceof ClassConstFetch) {
            return self::getClassConstantBits($codebase, $expr, $self, $static_is_self, $visited);
        }

        if ($expr instanceof BitwiseOr || $expr instanceof BitwiseAnd) {
            [$left_certain, $left_possible] = self::getExpressionBits(
                $codebase,
                $expr->left,
                $self,
                $static_is_self,
                $visited,
                $statements_analyzer,
            );
            [$right_certain, $right_possible] = self::getExpressionBits(
                $codebase,
                $expr->right,
                $self,
                $static_is_self,
                $visited,
                $statements_analyzer,
            );

            return $expr instanceof BitwiseOr
                ? [$left_certain | $right_certain, $left_possible | $right_possible]
                : [$left_certain & $right_certain, $left_possible & $right_possible];
        }

        if ($expr instanceof Ternary) {
            // the value is one of the branches: the first one of `?:` is the condition
            [$if_certain, $if_possible] = self::getExpressionBits(
                $codebase,
                $expr->if ?? $expr->cond,
                $self,
                $static_is_self,
                $visited,
                $statements_analyzer,
            );
            [$else_certain, $else_possible] = self::getExpressionBits(
                $codebase,
                $expr->else,
                $self,
                $static_is_self,
                $visited,
                $statements_analyzer,
            );

            return [$if_certain & $else_certain, $if_possible | $else_possible];
        }

        if ($expr instanceof Variable && is_string($expr->name) && $statements_analyzer !== null) {
            return self::getVariableBits(
                $codebase,
                $statements_analyzer,
                $expr,
                $expr->name,
                $self,
                $static_is_self,
                $visited,
            );
        }

        return self::UNKNOWN;
    }

    /**
     * A variable of a function-like that is only ever written by assignments of constant expressions has the bits
     * all of them have. Its inferred type, an int, tells it is set before it is read.
     *
     * @param array<string, true> $visited
     * @return array{int, int}
     */
    private static function getVariableBits(
        Codebase $codebase,
        StatementsAnalyzer $statements_analyzer,
        Variable $variable,
        string $name,
        ?ClassLikeStorage $self,
        bool $static_is_self,
        array $visited,
    ): array {
        $type = $statements_analyzer->node_data->getType($variable);
        $source = $statements_analyzer->getSource();

        if ($type === null
            || !$type->isInt()
            || $type->possibly_undefined
            || $type->possibly_undefined_from_try
            || isset($visited['$' . $name])
            || !$source instanceof FunctionLikeAnalyzer
        ) {
            return self::UNKNOWN;
        }

        $assigned = self::getAssignedValues($statements_analyzer, $source->getFunctionNode(), $name);

        if ($assigned === null || $assigned === []) {
            return self::UNKNOWN;
        }

        $visited['$' . $name] = true;
        $certain = -1;
        $possible = 0;

        foreach ($assigned as $value) {
            [$value_certain, $value_possible] = self::getExpressionBits(
                $codebase,
                $value,
                $self,
                $static_is_self,
                $visited,
                $statements_analyzer,
            );
            $certain &= $value_certain;
            $possible |= $value_possible;
        }

        return [$certain, $possible];
    }

    /**
     * The values the plain assignments of the function-like give the variable, or null if anything else may write
     * it: a parameter, a closure `use`, a reference, a by-reference argument, a loop, `unset()`, `global`, `static`,
     * `catch`, `$$name`, `extract()`, `eval()` or an included file. An arrow function reads the variables of the
     * code around it.
     *
     * @return list<Expr>|null
     */
    private static function getAssignedValues(
        StatementsAnalyzer $statements_analyzer,
        Closure|Function_|ClassMethod|ArrowFunction $function,
        string $name,
    ): ?array {
        if ($function instanceof ArrowFunction) {
            return null;
        }

        foreach ($function->getParams() as $param) {
            if ($param->var instanceof Variable && $param->var->name === $name) {
                return null;
            }
        }

        if ($function instanceof Closure) {
            foreach ($function->uses as $use) {
                if ($use->var->name === $name) {
                    return null;
                }
            }
        }

        $assigned = [];

        foreach ($function->getStmts() ?? [] as $stmt) {
            if (!self::collectAssignedValues($statements_analyzer, $stmt, $name, $assigned)) {
                return null;
            }
        }

        return $assigned;
    }

    /**
     * @param list<Expr> $assigned
     * @return bool false if the node may write the variable otherwise than by a plain assignment
     */
    private static function collectAssignedValues(
        StatementsAnalyzer $statements_analyzer,
        Node $node,
        string $name,
        array &$assigned,
    ): bool {
        if ($node instanceof Closure) {
            foreach ($node->uses as $use) {
                if ($use->byRef && $use->var->name === $name) {
                    return false;
                }
            }

            return true;
        }

        // their variables are their own
        if ($node instanceof FunctionLike || $node instanceof ClassLike) {
            return true;
        }

        if (($node instanceof Variable && !is_string($node->name))
            || $node instanceof Eval_
            || $node instanceof Include_
            || ($node instanceof FuncCall && self::writesTheScope($node))
        ) {
            return false;
        }

        if ($node instanceof Assign && $node->var instanceof Variable && $node->var->name === $name) {
            $assigned[] = $node->expr;

            return self::collectAssignedValues($statements_analyzer, $node->expr, $name, $assigned);
        }

        $targets = match (true) {
            $node instanceof Assign,
            $node instanceof AssignOp,
            $node instanceof PreInc,
            $node instanceof PreDec,
            $node instanceof PostInc,
            $node instanceof PostDec => [$node->var],
            $node instanceof AssignRef => [$node->var, $node->expr],
            $node instanceof Foreach_ => [$node->keyVar, $node->valueVar, $node->byRef ? $node->expr : null],
            $node instanceof Unset_,
            $node instanceof Global_ => $node->vars,
            $node instanceof Static_ => array_map(static fn(StaticVar $var): Variable => $var->var, $node->vars),
            $node instanceof Catch_ => [$node->var],
            $node instanceof ArrayItem && $node->byRef => [$node->value],
            $node instanceof CallLike && !$node->isFirstClassCallable()
                && !self::takesFlagsByValue($statements_analyzer, $node)
                    => array_map(static fn(Arg $arg): Expr => $arg->value, $node->getArgs()),
            default => [],
        };

        foreach ($targets as $target) {
            if ($target !== null && (new NodeFinder())->findFirst(
                $target,
                static fn(Node $found): bool => $found instanceof Variable && $found->name === $name,
            ) !== null) {
                return false;
            }
        }

        foreach ($node->getSubNodeNames() as $sub_node_name) {
            /** @var mixed $sub_node */
            $sub_node = $node->$sub_node_name;

            /** @var mixed $child */
            foreach (is_array($sub_node) ? $sub_node : [$sub_node] as $child) {
                if ($child instanceof Node
                    && !self::collectAssignedValues($statements_analyzer, $child, $name, $assigned)
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Whether the call may set variables of the scope it is in: `extract()`, and before PHP 8 `parse_str()` and
     * `mb_parse_str()` without a result. They can't be called dynamically.
     */
    private static function writesTheScope(FuncCall $call): bool
    {
        if (!$call->name instanceof Name || $call->isFirstClassCallable()) {
            return false;
        }

        $function_id = strtolower($call->name->getLast());

        return $function_id === 'extract'
            || (($function_id === 'parse_str' || $function_id === 'mb_parse_str') && count($call->getArgs()) < 2);
    }

    /**
     * Whether the call is one of a builtin function that takes its flags, and every other argument, by value
     */
    private static function takesFlagsByValue(StatementsAnalyzer $statements_analyzer, CallLike $call): bool
    {
        if (!$call instanceof FuncCall || !$call->name instanceof Name) {
            return false;
        }

        $function_id = strtolower($call->name->getLast());

        return in_array($function_id, self::FLAG_FUNCTIONS, true)
            && self::isBuiltin($statements_analyzer, $call->name, $function_id);
    }

    /**
     * Whether the name calls the builtin function, not a function of the namespace named the same
     *
     * @param lowercase-string $function_id
     */
    public static function isBuiltin(
        StatementsAnalyzer $statements_analyzer,
        Name $name,
        string $function_id,
    ): bool {
        $functions = $statements_analyzer->getCodebase()->functions;

        $called_id = strtolower($name instanceof Name\FullyQualified
            ? $name->toString()
            : $functions->getFullyQualifiedFunctionNameFromString($name->toString(), $statements_analyzer));

        if ($called_id === $function_id) {
            return true;
        }

        // an unqualified name falls back to the global function when the namespace has none
        return !$name instanceof Name\FullyQualified
            && strtolower($name->toString()) === $function_id
            && !$functions->functionExists($statements_analyzer, $called_id);
    }

    /**
     * The value of a builtin int constant. The name resolver leaves an unqualified name in a namespace without
     * `use const` unresolved: it may name a constant of that namespace.
     */
    private static function getBuiltinConstant(Name $name): ?int
    {
        $resolved_name = $name->getAttribute('resolvedName');

        if (!is_string($resolved_name)) {
            return null;
        }

        foreach (get_defined_constants(true) as $extension => $constants) {
            if ($extension !== 'user' && isset($constants[$resolved_name])) {
                return is_int($constants[$resolved_name]) ? $constants[$resolved_name] : null;
            }
        }

        return null;
    }

    /**
     * The bits of the value declared for the constant, in the scope of its declaration
     *
     * @param array<string, true> $visited
     * @return array{int, int}
     */
    private static function getClassConstantBits(
        Codebase $codebase,
        ClassConstFetch $fetch,
        ?ClassLikeStorage $self,
        bool $static_is_self,
        array $visited,
    ): array {
        if (!$fetch->class instanceof Name
            || !$fetch->name instanceof Identifier
            // class_alias() may give any class any name
            || self::hasDynamicClassAlias()
        ) {
            return self::UNKNOWN;
        }

        $scope = $fetch->class->toLowerString();

        $class = match (true) {
            $scope === 'self' => $self,
            $scope === 'static' => $static_is_self ? $self : null,
            $scope === 'parent' => $self !== null && $self->parent_class !== null
                ? self::getClass($codebase, $self->parent_class)
                : null,
            default => self::getNamedClass($codebase, $fetch->class),
        };

        if ($class === null) {
            return self::UNKNOWN;
        }

        $name = $fetch->name->name;
        $constant = $class->constants[$name] ?? null;
        $location = $constant?->stmt_location;
        $id = strtolower($class->name) . '::' . $name;

        if ($constant === null
            || $location === null
            || isset($visited[$id])
            // static:: is the class of the call only where no class extending it can redeclare the constant: a
            // natively final class or constant (a class with a docblock @final can have one)
            || ($scope === 'static' && !($class->final && !$class->final_from_docblock) && !$constant->final)
        ) {
            return self::UNKNOWN;
        }

        // the class or interface declaring the constant gives the self:: and parent:: of its value: one a trait
        // declares isn't found, the self:: of its value being the class using it
        $declaring_class = null;

        foreach ([
            $class->name,
            ...array_values($class->parent_classes),
            ...array_values($class->class_implements),
            ...array_values($class->parent_interfaces),
        ] as $candidate) {
            $candidate_storage = self::getClass($codebase, $candidate);
            $declaration = $candidate_storage?->stmt_location;

            if ($declaration !== null
                && $declaration->file_path === $location->file_path
                && $declaration->raw_file_start <= $location->raw_file_start
                && $location->raw_file_end <= $declaration->raw_file_end
            ) {
                $declaring_class = $candidate_storage;
                break;
            }
        }

        if ($declaring_class === null) {
            return self::UNKNOWN;
        }

        $start = $location->raw_file_start;
        $const = (new NodeFinder())->findFirst(
            $codebase->getStatementsForFile($location->file_path),
            static fn(Node $node): bool => $node instanceof Const_ && $node->getStartFilePos() === $start,
        );

        if (!$const instanceof Const_) {
            return self::UNKNOWN;
        }

        $visited[$id] = true;

        return self::getExpressionBits($codebase, $const->value, $declaring_class, false, $visited);
    }

    /**
     * Whether a class_alias() call of the code psalm scanned may give a class a name it doesn't know
     *
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function hasDynamicClassAlias(): bool
    {
        foreach (FileStorageProvider::getAll() as $file_storage) {
            if ($file_storage->has_dynamic_class_alias) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a class_alias() call of the code psalm scanned gives a class the name, whatever the class: one it
     * skips, or that aliases a class it doesn't know, isn't one of its aliases
     *
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function isPossibleClassAlias(string $name): bool
    {
        $name_lc = strtolower($name);

        foreach (FileStorageProvider::getAll() as $file_storage) {
            if (isset($file_storage->possible_class_aliases[$name_lc])) {
                return true;
            }
        }

        return false;
    }

    /**
     * The class of the name, unless it is a name class_alias() gives a class, which may be another one at runtime
     */
    private static function getNamedClass(Codebase $codebase, Name $class_name): ?ClassLikeStorage
    {
        $name = $class_name->getAttribute('resolvedName');

        if (!is_string($name) || $codebase->classlikes->isClassAlias($name) || self::isPossibleClassAlias($name)) {
            return null;
        }

        $class = self::getClass($codebase, $name);

        return $class !== null && strtolower($class->name) === strtolower($name) ? $class : null;
    }

    /**
     * A class whose declaration psalm knows is the one that runs, and so are those of the classes and interfaces it
     * extends or implements: else, a `parent::` or an inherited constant may be another one at runtime. It is not a
     * trait, whose self:: is the class using it.
     */
    private static function getClass(Codebase $codebase, string $name): ?ClassLikeStorage
    {
        if (!$codebase->classlike_storage_provider->has($name)) {
            return null;
        }

        $class = $codebase->classlike_storage_provider->get($name);

        if ($class->is_trait || !self::isKnownDeclaration($codebase, $class)) {
            return null;
        }

        foreach ([
            ...array_values($class->parent_classes),
            ...array_values($class->class_implements),
            ...array_values($class->parent_interfaces),
        ] as $ancestor) {
            if (!self::isBuiltinClass($ancestor)
                && (!$codebase->classlike_storage_provider->has($ancestor)
                    || !self::isKnownDeclaration($codebase, $codebase->classlike_storage_provider->get($ancestor)))
            ) {
                return null;
            }
        }

        return $class;
    }

    /**
     * Whether the class is one of the project, not a stub, declared once, by a statement of its file or namespace
     * (not in a condition or a function-like)
     */
    private static function isKnownDeclaration(Codebase $codebase, ClassLikeStorage $class): bool
    {
        $location = $class->stmt_location;

        if (!$class->user_defined
            || $class->stubbed
            // a duplicate declaration is a visitor issue
            || $class->has_visitor_issues
            || $location === null
        ) {
            return false;
        }

        foreach ($codebase->getStatementsForFile($location->file_path) as $stmt) {
            foreach ($stmt instanceof Namespace_ ? $stmt->stmts : [$stmt] as $declaration) {
                if ($declaration instanceof ClassLike
                    && $declaration->getStartFilePos() === $location->raw_file_start
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Whether the class or interface is one of PHP, which no code can declare again
     */
    private static function isBuiltinClass(string $name): bool
    {
        return (class_exists($name, false) || interface_exists($name, false))
            && (new ReflectionClass($name))->isInternal();
    }
}
