<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\AddRemoveTaints;

use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BitwiseAnd;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeFinder;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Storage\ClassLikeStorage;
use ReflectionClass;

use function array_values;
use function class_exists;
use function get_defined_constants;
use function interface_exists;
use function is_int;
use function is_string;
use function strtolower;

/**
 * The bits of the flags of a call, which tell the escapes it does. Only a compile-time constant expression tells them:
 * an int literal, a builtin constant (no code can redefine one), a class constant whose declared value is such an
 * expression, and `|`, `&` and ternaries of them. Anything else (a variable, a property, a parameter, a call) may hold
 * any bits whatever its inferred type, which an assert() compiled out in production, a `@var`, a `@param` with a
 * suppressed or baselined wrong caller or a `$$name` write can make a literal the runtime value doesn't have.
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

        return self::getExpressionBits($codebase, $expr, $self, true, []);
    }

    /**
     * @param bool $static_is_self whether `static::` may be the class of $self (it can't be in the value of a constant)
     * @param array<string, true> $visited the class constants being evaluated, against cycles
     * @return array{int, int}
     */
    private static function getExpressionBits(
        Codebase $codebase,
        Expr $expr,
        ?ClassLikeStorage $self,
        bool $static_is_self,
        array $visited,
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
            );
            [$right_certain, $right_possible] = self::getExpressionBits(
                $codebase,
                $expr->right,
                $self,
                $static_is_self,
                $visited,
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
            );
            [$else_certain, $else_possible] = self::getExpressionBits(
                $codebase,
                $expr->else,
                $self,
                $static_is_self,
                $visited,
            );

            return [$if_certain & $else_certain, $if_possible | $else_possible];
        }

        return self::UNKNOWN;
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
