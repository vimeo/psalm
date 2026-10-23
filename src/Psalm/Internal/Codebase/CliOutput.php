<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\TaintKind;
use UnexpectedValueException;

use function array_key_exists;
use function array_pop;
use function count;
use function strrpos;
use function strtolower;
use function substr;

/**
 * What the code that only runs from the command line prints goes to a terminal, not to an HTML response: in the
 * function-likes with `@psalm-cli-only`, the methods of the classes with it, and the closures in their bodies, what
 * echo, print, printf(), vprintf(), var_dump(), debug_zval_dump(), and print_r() and var_export() not returning it
 * print isn't an HTML sink. They stay sinks of the other taints (secrets), and the values stay tainted elsewhere.
 *
 * @internal
 */
final class CliOutput
{
    /** The taints that aren't reported for what such code prints */
    public const HTML_TAINTS = TaintKind::INPUT_HTML | TaintKind::INPUT_HAS_QUOTES;

    /** The attribute of the arguments of a call printing them from code only running from the command line */
    public const PRINTED_ATTRIBUTE = 'cliOutputPrinted';

    /**
     * The builtin functions printing their arguments => the offset of the argument making them return it instead
     */
    private const PRINTING_FUNCTIONS = [
        'printf' => null,
        'vprintf' => null,
        'var_dump' => null,
        'debug_zval_dump' => null,
        'print_r' => 1,
        'var_export' => 1,
    ];

    /**
     * Whether the code being analyzed only runs from the command line: that of a function-like with `@psalm-cli-only`,
     * of a method of a class with it, or of a closure in their bodies
     */
    public static function isInCliOnlyCode(StatementsAnalyzer $statements_analyzer): bool
    {
        $source = $statements_analyzer->getSource();

        // a closure runs in the process of the code creating it
        while ($source instanceof ClosureAnalyzer) {
            if ($source->getFunctionLikeStorage($statements_analyzer)->cli_only) {
                return true;
            }

            $source = $source->getSource();
            if ($source instanceof StatementsAnalyzer) {
                $source = $source->getSource();
            }
        }

        if (!$source instanceof FunctionLikeAnalyzer) {
            return false;
        }

        if ($source->getFunctionLikeStorage($statements_analyzer)->cli_only) {
            return true;
        }

        $codebase = $statements_analyzer->getCodebase();
        $class = $source instanceof MethodAnalyzer ? $source->getFQCLN() : null;

        return $class !== null
            && $codebase->classlike_storage_provider->has($class)
            && $codebase->classlike_storage_provider->get($class)->cli_only;
    }

    /**
     * Marks `@psalm-cli-only` the methods of $class only its code running from the command line calls: the private
     * ones, and the protected ones of a final class overriding none, called (directly or through such methods) from
     * its methods with `@psalm-cli-only`, but not from its other methods, nor from the methods of the traits it uses,
     * nor named by a callable (`[$this, 'show']`, `'self::show'`). A call of a method by a name computed at runtime
     * from code that may run elsewhere marks none: it could call any.
     */
    public static function markCliOnlyCallees(Codebase $codebase, ClassLikeStorage $storage, ClassLike $class): void
    {
        if (!$codebase->taint_flow_graph || $storage->cli_only) {
            return;
        }

        // method => the names of the methods it calls
        $calls = [];
        $cli_methods = [];
        $other_methods = [];
        $candidates = [];

        foreach ($class->getMethods() as $method) {
            $name = $method->name->toLowerString();
            $method_storage = $storage->methods[$name] ?? null;
            $called = self::getCalledMethods($method);

            if ($method_storage?->cli_only === true) {
                $cli_methods[] = $name;
            } elseif ($called === null) {
                return;
            } elseif ($method_storage !== null
                && $name !== '__construct'
                && $name !== '__destruct'
                && ($method->isPrivate()
                    || ($method->isProtected()
                        && $storage->final
                        && ($storage->overridden_method_ids[$name] ?? []) === []))
            ) {
                $candidates[$name] = true;
            } else {
                $other_methods[] = $name;
            }

            $calls[$name] = $called ?? [];
        }

        if ($cli_methods === [] || $candidates === []) {
            return;
        }

        $nodes = [$class];
        $traits = $storage->used_traits;
        while ($traits !== []) {
            $trait = array_pop($traits);

            try {
                $trait_node = $codebase->classlikes->getTraitNode($trait);
            } catch (UnexpectedValueException) {
                return;
            }

            $nodes[] = $trait_node;
            $traits = [...$traits, ...$codebase->classlike_storage_provider->get($trait)->used_traits];

            foreach ($trait_node->getMethods() as $i => $method) {
                $called = self::getCalledMethods($method);
                if ($called === null) {
                    return;
                }

                $calls[' ' . $trait . ' ' . $i] = $called;
                $other_methods[] = ' ' . $trait . ' ' . $i;
            }
        }

        // the methods callables name: `[$this, 'show']`, `'self::show'`
        $node_finder = new NodeFinder();
        foreach ($node_finder->findInstanceOf($nodes, Array_::class) as $array) {
            if (count($array->items) === 2
                && isset($array->items[1])
                && $array->items[1]->value instanceof String_
            ) {
                $other_methods[] = strtolower($array->items[1]->value->value);
            }
        }

        foreach ($node_finder->findInstanceOf($nodes, String_::class) as $string) {
            $method_name_offset = strrpos($string->value, '::');
            if ($method_name_offset !== false) {
                $other_methods[] = strtolower(substr($string->value, $method_name_offset + 2));
            }
        }

        $called_elsewhere = self::getReachable($calls, $other_methods);

        foreach (self::getReachable($calls, $cli_methods) as $name => $_) {
            if (array_key_exists($name, $candidates) && !array_key_exists($name, $called_elsewhere)) {
                $storage->methods[$name]->cli_only = true;
            }
        }
    }

    /**
     * The names of the methods $method calls, also in its closures; null if it calls one by a name computed at runtime
     *
     * @return list<lowercase-string>|null
     */
    private static function getCalledMethods(ClassMethod $method): ?array
    {
        $called = [];

        $calls = (new NodeFinder())->find(
            $method->stmts ?? [],
            static fn(Node $node): bool => $node instanceof MethodCall
                || $node instanceof NullsafeMethodCall
                || $node instanceof StaticCall
                || $node instanceof Array_,
        );

        foreach ($calls as $call) {
            if ($call instanceof Array_) {
                // a callable of the object or the class whose method is named at runtime, like [$this, $name]
                if (count($call->items) === 2
                    && isset($call->items[0], $call->items[1])
                    && ($call->items[0]->value instanceof Variable
                        || $call->items[0]->value instanceof ClassConstFetch)
                    && !$call->items[1]->value instanceof String_
                ) {
                    return null;
                }
            } elseif ($call->name instanceof Identifier) {
                $called[] = $call->name->toLowerString();
            } else {
                return null;
            }
        }

        return $called;
    }

    /**
     * The methods $roots call, $roots included
     *
     * @param array<string, list<lowercase-string>> $calls
     * @param list<string> $roots
     * @return array<string, true>
     * @psalm-pure
     */
    private static function getReachable(array $calls, array $roots): array
    {
        $reachable = [];

        while ($roots !== []) {
            $method = array_pop($roots);
            if (array_key_exists($method, $reachable)) {
                continue;
            }

            $reachable[$method] = true;
            $roots = [...$roots, ...($calls[$method] ?? [])];
        }

        return $reachable;
    }

    /**
     * Marks the arguments of a call of a builtin function printing them, in code only running from the command line
     *
     * @param array<array-key, Arg> $args
     */
    public static function markPrintedArgs(
        StatementsAnalyzer $statements_analyzer,
        ?string $function_id,
        array $args,
    ): void {
        if ($function_id === null
            || !array_key_exists($function_id, self::PRINTING_FUNCTIONS)
            || !$statements_analyzer->taint_flow_graph
        ) {
            return;
        }

        $return_offset = self::PRINTING_FUNCTIONS[$function_id];
        if ($return_offset !== null && isset($args[$return_offset])) {
            // what it returns may go anywhere: only a literal false makes it print
            $return_type = $statements_analyzer->node_data->getType($args[$return_offset]->value);
            if ($return_type === null || !$return_type->isFalse()) {
                return;
            }
        }

        if (!self::isInCliOnlyCode($statements_analyzer)) {
            return;
        }

        foreach ($args as $offset => $arg) {
            if ($offset !== $return_offset && $arg->name === null && !$arg->unpack) {
                $arg->value->setAttribute(self::PRINTED_ATTRIBUTE, true);
            }
        }
    }
}
