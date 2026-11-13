<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeFinder;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\CliOnlyCall;
use Psalm\Issue\CliOnlyInstancesUnguarded;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\TaintKind;

use function array_key_exists;
use function in_array;
use function is_string;
use function strtolower;

/**
 * What the code that only runs from the command line prints goes to a terminal, not to an HTML response: in the
 * function-likes with `@psalm-cli-only`, the methods of the classes with it, the top-level code of the files a plugin
 * marked (BeforeFileAnalysisEvent::markAsCliOnly()), the code a PHP_SAPI check guards, and
 * the closures in their bodies, what echo, print, printf(), vprintf(), var_dump(), debug_zval_dump(), and print_r()
 * and var_export() not returning it print isn't an HTML sink. They stay sinks of the other taints (secrets), and the
 * values stay tainted elsewhere. Such code is only called from code only running from the command line too.
 *
 * The instances of a class with `@psalm-cli-only-instances` only exist in command-line processes: its constructor and
 * the method unserialize() calls start with a PHP_SAPI check (checkInstanceGuard()). The code that can only run with
 * one (its instance methods, and the function-likes with a parameter requiring one) runs from the command line too,
 * and so does the code calling it.
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

    /** The SAPIs running scripts from the command line */
    private const CLI_SAPIS = ['cli', 'phpdbg'];

    /**
     * The methods running on an object of a class with `@psalm-cli-only-instances` that no guard checked yet: each of
     * them has to start with the guard (see checkInstanceGuard())
     */
    private const INSTANCE_GUARDS = ['__construct', '__unserialize', '__wakeup'];

    /**
     * Whether the code being analyzed only runs from the command line: that of a function-like with `@psalm-cli-only`
     * or requiring an instance of a class with `@psalm-cli-only-instances`, of a method of a class with the former, of
     * an instance method of a class with the latter, the top-level code of a file a plugin marked (or included from
     * such code), the code a PHP_SAPI or php_sapi_name() check guards, or a closure in them
     */
    public static function isInCliOnlyCode(StatementsAnalyzer $statements_analyzer, Context $context): bool
    {
        if (self::isNarrowedToCli($context)) {
            return true;
        }

        $codebase = $statements_analyzer->getCodebase();
        $source = $statements_analyzer->getSource();

        // a closure runs in the process of the code creating it
        while ($source instanceof ClosureAnalyzer) {
            $closure_storage = $source->getFunctionLikeStorage($statements_analyzer);
            if ($closure_storage->cli_only || self::requiresCliOnlyInstance($codebase, $closure_storage)) {
                return true;
            }

            $source = $source->getSource();
            if ($source instanceof StatementsAnalyzer) {
                $source = $source->getSource();
            }
        }

        if ($source instanceof FileAnalyzer || $source instanceof NamespaceAnalyzer) {
            return $statements_analyzer->getFileAnalyzer()->cli_only;
        }

        if (!$source instanceof FunctionLikeAnalyzer) {
            return false;
        }

        $storage = $source->getFunctionLikeStorage($statements_analyzer);
        if ($storage->cli_only || self::requiresCliOnlyInstance($codebase, $storage)) {
            return true;
        }

        // the class using it, for a trait method
        $class = $source instanceof MethodAnalyzer ? $context->self : null;

        return $class !== null
            && $codebase->classlike_storage_provider->has($class)
            && ($codebase->classlike_storage_provider->get($class)->cli_only
                || self::runsOnCliOnlyInstance($codebase, $storage, $class));
    }

    /**
     * Whether a PHP_SAPI or php_sapi_name() check, or a function or method asserting PHP_SAPI, narrowed the SAPI to the
     * command line
     *
     * @psalm-capabilities read-props
     */
    public static function isNarrowedToCli(Context $context): bool
    {
        $sapi_type = $context->vars_in_scope[Context::PHP_SAPI_VAR_ID] ?? null;
        if ($sapi_type === null) {
            return false;
        }

        foreach ($sapi_type->getAtomicTypes() as $atomic) {
            if (!$atomic instanceof TLiteralString || !in_array($atomic->value, self::CLI_SAPIS, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the instances of the class, or of a class it extends, only exist in command-line processes
     * (`@psalm-cli-only-instances`)
     *
     * @psalm-capabilities read-props
     */
    public static function hasCliOnlyInstances(Codebase $codebase, string $fq_class_name): bool
    {
        $provider = $codebase->classlike_storage_provider;
        if (!$provider->has($fq_class_name)) {
            return false;
        }

        $storage = $provider->get($fq_class_name);
        if ($storage->cli_only_instances) {
            return true;
        }

        foreach ($storage->parent_classes as $parent_class) {
            if ($provider->has($parent_class) && $provider->get($parent_class)->cli_only_instances) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the function-like only runs with an instance of a class with `@psalm-cli-only-instances`: the native type
     * of one of its parameters requires one (alone or in an intersection), and the parameter has no default, isn't
     * nullable and isn't variadic. Without one, PHP throws a TypeError before the body runs, however it is called. A
     * docblock type proves nothing: PHP doesn't check it.
     *
     * @psalm-capabilities read-props
     */
    public static function requiresCliOnlyInstance(Codebase $codebase, FunctionLikeStorage $storage): bool
    {
        foreach ($storage->params as $param) {
            if ($param->is_optional
                || $param->is_variadic
                || $param->signature_type === null
                || !$param->signature_type->isSingle()
            ) {
                continue;
            }

            $atomic = $param->signature_type->getSingleAtomic();
            if (!$atomic instanceof TNamedObject) {
                continue;
            }

            foreach ([$atomic, ...$atomic->extra_types] as $part) {
                if ($part instanceof TNamedObject && self::hasCliOnlyInstances($codebase, $part->value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Whether the method runs on an instance of the class, which has `@psalm-cli-only-instances`, as `$this`: it isn't
     * static, and it isn't one of the methods guarding the instances (the constructor, `__unserialize()` and
     * `__wakeup()`), which run on an object not checked yet
     *
     * @psalm-capabilities read-props
     */
    private static function runsOnCliOnlyInstance(
        Codebase $codebase,
        FunctionLikeStorage $storage,
        string $fq_class_name,
    ): bool {
        return $storage instanceof MethodStorage
            && !$storage->is_static
            && $storage->cased_name !== null
            && !in_array(strtolower($storage->cased_name), self::INSTANCE_GUARDS, true)
            && self::hasCliOnlyInstances($codebase, $fq_class_name);
    }

    /**
     * Marks the arguments of a call of a builtin function printing them, in code only running from the command line.
     * The mark is written on every analysis of the call: the nodes of a trait method are analyzed once for each class
     * using it, and only some of them may be command-line code.
     *
     * @param array<array-key, Arg> $args
     */
    public static function markPrintedArgs(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
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
        $printed = true;
        if ($return_offset !== null && isset($args[$return_offset])) {
            // what it returns may go anywhere: only a literal false makes it print
            $return_type = $statements_analyzer->node_data->getType($args[$return_offset]->value);
            $printed = $return_type !== null && $return_type->isFalse();
        }

        $printed = $printed && self::isInCliOnlyCode($statements_analyzer, $context);

        foreach ($args as $offset => $arg) {
            if ($offset !== $return_offset && $arg->name === null && !$arg->unpack) {
                $arg->value->setAttribute(self::PRINTED_ATTRIBUTE, $printed);
            }
        }
    }

    /**
     * Whether the method only runs from the command line: it has `@psalm-cli-only`, or the class it appears in does
     *
     * @psalm-capabilities read-props
     */
    public static function isCliOnlyMethod(Codebase $codebase, MethodIdentifier $method_id): bool
    {
        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);
        if ($declaring_method_id === null) {
            return false;
        }

        if ($codebase->methods->getStorage($declaring_method_id)->cli_only) {
            return true;
        }

        $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);

        return $appearing_method_id !== null
            && $codebase->classlike_storage_provider->has($appearing_method_id->fq_class_name)
            && $codebase->classlike_storage_provider->get($appearing_method_id->fq_class_name)->cli_only;
    }

    /**
     * Reports the call of a method only running from the command line from code that may not. Calling it with an
     * instance of a class with `@psalm-cli-only-instances` (its receiver or a parameter requiring one) is fine: the
     * caller holds one, so it runs from the command line too.
     */
    public static function checkMethodCall(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        MethodIdentifier $method_id,
        CodeLocation $code_location,
    ): void {
        $codebase = $statements_analyzer->getCodebase();
        if (!self::isCliOnlyMethod($codebase, $method_id)) {
            return;
        }

        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);
        if ($declaring_method_id === null) {
            return;
        }

        $storage = $codebase->methods->getStorage($declaring_method_id);
        if (self::requiresCliOnlyInstance($codebase, $storage)
            || self::runsOnCliOnlyInstance($codebase, $storage, $method_id->fq_class_name)
        ) {
            return;
        }

        self::checkCall(
            $statements_analyzer,
            $context,
            'method ' . $codebase->methods->getCasedMethodId($method_id) . '()',
            $code_location,
        );
    }

    /**
     * Reports the instantiation of a class only running from the command line, or with such a constructor, from code
     * that may not
     */
    public static function checkInstantiation(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        ClassLikeStorage $storage,
        New_ $stmt,
    ): void {
        $codebase = $statements_analyzer->getCodebase();
        $constructor_id = new MethodIdentifier($storage->name, '__construct');

        $declaring_constructor_id = $codebase->methods->getDeclaringMethodId($constructor_id);
        if ($declaring_constructor_id !== null
            && self::requiresCliOnlyInstance($codebase, $codebase->methods->getStorage($declaring_constructor_id))
        ) {
            // the code creating it passes an instance of a class with `@psalm-cli-only-instances`
            return;
        }

        if ($storage->cli_only) {
            $callee = 'class ' . $storage->name;
        } elseif (self::isCliOnlyMethod($codebase, $constructor_id)) {
            $callee = 'method ' . $codebase->methods->getCasedMethodId($constructor_id) . '()';
        } else {
            return;
        }

        self::checkCall($statements_analyzer, $context, $callee, new CodeLocation($statements_analyzer, $stmt));
    }

    /**
     * Reports the call of code only running from the command line from code that may not
     *
     * @param string $callee what is called, as written in the issue: `function f()`, `method A::f()`, `class A`
     */
    public static function checkCall(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        string $callee,
        CodeLocation $code_location,
    ): void {
        if ($context->collect_initializations
            || $context->collect_mutations
            || self::isInCliOnlyCode($statements_analyzer, $context)
        ) {
            return;
        }

        IssueBuffer::maybeAdd(
            new CliOnlyCall(
                'The ' . $callee . ' only runs from the command line (@psalm-cli-only), but this code may run'
                    . ' outside of it: mark it @psalm-cli-only too, or check that PHP_SAPI === \'cli\' first',
                $code_location,
            ),
            $statements_analyzer->getSuppressedIssues(),
        );
    }

    /**
     * Reports a class with `@psalm-cli-only-instances` whose instances could exist outside of the command line. The
     * guards are the constructor and the method unserialize() calls (`__unserialize()`, or `__wakeup()` without it),
     * which checkInstanceGuard() checks: the class has to declare them, and be final, or a subclass could add a
     * constructor or an `__unserialize()` of its own. It can't implement Serializable, whose unserialize() runs on an
     * object created without the constructor and before `__wakeup()`.
     *
     * @param array<string> $suppressed_issues
     */
    public static function checkCliOnlyInstancesClass(
        ClassLikeStorage $storage,
        array $suppressed_issues,
    ): void {
        if (!$storage->cli_only_instances) {
            return;
        }

        $problems = [];

        if (!$storage->final) {
            $problems[] = 'it isn\'t final, so a subclass could add a constructor or an __unserialize() without'
                . ' a guard';
        }

        if (isset($storage->class_implements['serializable'])) {
            $problems[] = 'it implements Serializable, whose unserialize() runs on an object no guard checked';
        }

        if (!self::declaresMethod($storage, '__construct')) {
            $problems[] = 'it doesn\'t declare a constructor';
        }

        $unserialize_method = isset($storage->declaring_method_ids['__unserialize']) ? '__unserialize' : '__wakeup';
        if (!self::declaresMethod($storage, $unserialize_method)) {
            $problems[] = 'it doesn\'t declare ' . $unserialize_method . '(), which unserialize() calls instead of'
                . ' the constructor';
        }

        $location = $storage->location ?? $storage->stmt_location;
        if ($location === null) {
            return;
        }

        foreach ($problems as $problem) {
            IssueBuffer::maybeAdd(
                new CliOnlyInstancesUnguarded(
                    'The instances of ' . $storage->name . ' may exist outside of the command line'
                        . ' (@psalm-cli-only-instances): ' . $problem,
                    $location,
                ),
                $suppressed_issues,
            );
        }
    }

    /**
     * Whether the method guards the instances of a class with `@psalm-cli-only-instances` (see checkInstanceGuard()):
     * the class's constructor, or the method unserialize() calls
     *
     * @psalm-capabilities read-props
     */
    public static function isInstanceGuard(Codebase $codebase, MethodStorage $storage, Context $context): bool
    {
        if ($context->collect_initializations
            || $context->collect_mutations
            || $context->self === null
            || $storage->cased_name === null
            || !$codebase->classlike_storage_provider->has($context->self)
        ) {
            return false;
        }

        $class_storage = $codebase->classlike_storage_provider->get($context->self);
        $method_name = strtolower($storage->cased_name);

        return $class_storage->cli_only_instances
            && in_array($method_name, self::INSTANCE_GUARDS, true)
            && ($method_name !== '__wakeup' || !isset($class_storage->declaring_method_ids['__unserialize']));
    }

    /**
     * Reports a guard of the instances of a class with `@psalm-cli-only-instances` (see isInstanceGuard()) that doesn't
     * start with the guard: its first statement has to throw, or leave the SAPI narrowed to the command line, like
     * `if (PHP_SAPI !== 'cli') { throw ...; }` or a call of a function asserting `'cli' PHP_SAPI`, without using the
     * object (`$this`, a closure binding it, a call through `self::`, `static::` or `parent::`). A promoted constructor
     * property can't have hooks, which run before the body.
     */
    public static function checkInstanceGuard(StatementsAnalyzer $statements_analyzer, ClassMethod $method): void
    {
        $guard = $method->stmts[0] ?? null;

        if ($guard === null) {
            $problem = 'it is empty';
        } elseif (!($guard instanceof Expression && $guard->expr instanceof Throw_)
            && !$statements_analyzer->cli_instance_guarded
        ) {
            $problem = 'its first statement doesn\'t check that PHP_SAPI is \'cli\'';
        } elseif (self::usesThis($guard)) {
            $problem = 'its first statement uses $this before the check';
        } else {
            $problem = null;
            foreach ($method->params as $param) {
                if ($param->hooks !== []) {
                    $problem = 'the hooks of its promoted property $'
                        . ($param->var instanceof Variable && is_string($param->var->name) ? $param->var->name : '')
                        . ' run before the check';
                    break;
                }
            }
        }

        if ($problem === null) {
            return;
        }

        IssueBuffer::maybeAdd(
            new CliOnlyInstancesUnguarded(
                'The method ' . $method->name->name . '() of a class with @psalm-cli-only-instances has to start'
                    . ' with a check that it runs from the command line, like if (PHP_SAPI !== \'cli\')'
                    . ' { throw ...; }, before using $this: ' . $problem,
                new CodeLocation($statements_analyzer, $method->name),
            ),
            $statements_analyzer->getSuppressedIssues(),
        );
    }

    /**
     * Whether the method appears in the class: declared in it, or in a trait it uses
     *
     * @psalm-mutation-free
     */
    private static function declaresMethod(ClassLikeStorage $storage, string $method_name): bool
    {
        return isset($storage->appearing_method_ids[$method_name])
            && strtolower($storage->appearing_method_ids[$method_name]->fq_class_name) === strtolower($storage->name);
    }

    /**
     * Whether the statement may use the object of the method it starts
     */
    private static function usesThis(Stmt $stmt): bool
    {
        return (new NodeFinder())->findFirst(
            $stmt,
            static fn(Node $node): bool => ($node instanceof Variable
                    && (!is_string($node->name) || $node->name === 'this'))
                || ($node instanceof StaticCall
                    && $node->class instanceof Name
                    && in_array($node->class->toLowerString(), ['self', 'static', 'parent'], true))
                || (($node instanceof Closure || $node instanceof ArrowFunction) && !$node->static),
        ) !== null;
    }
}
