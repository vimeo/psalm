<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\New_;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\CliOnlyCall;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\TaintKind;

use function array_key_exists;
use function in_array;

/**
 * What the code that only runs from the command line prints goes to a terminal, not to an HTML response: in the
 * function-likes with `@psalm-cli-only`, the methods of the classes with it, the code a PHP_SAPI check guards, and
 * the closures in their bodies, what echo, print, printf(), vprintf(), var_dump(), debug_zval_dump(), and print_r()
 * and var_export() not returning it print isn't an HTML sink. They stay sinks of the other taints (secrets), and the
 * values stay tainted elsewhere. Such code is only called from code only running from the command line too.
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
     * Whether the code being analyzed only runs from the command line: that of a function-like with `@psalm-cli-only`,
     * of a method of a class with it, the code a PHP_SAPI or php_sapi_name() check guards, or a closure in them
     */
    public static function isInCliOnlyCode(StatementsAnalyzer $statements_analyzer, Context $context): bool
    {
        $sapi_type = $context->vars_in_scope[Context::PHP_SAPI_VAR_ID] ?? null;
        if ($sapi_type !== null) {
            $is_cli_sapi = true;
            foreach ($sapi_type->getAtomicTypes() as $atomic) {
                if (!$atomic instanceof TLiteralString || !in_array($atomic->value, self::CLI_SAPIS, true)) {
                    $is_cli_sapi = false;
                    break;
                }
            }

            if ($is_cli_sapi) {
                return true;
            }
        }

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
        // the class using it, for a trait method
        $class = $source instanceof MethodAnalyzer ? $context->self : null;

        return $class !== null
            && $codebase->classlike_storage_provider->has($class)
            && $codebase->classlike_storage_provider->get($class)->cli_only;
    }

    /**
     * Marks the arguments of a call of a builtin function printing them, in code only running from the command line
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
        if ($return_offset !== null && isset($args[$return_offset])) {
            // what it returns may go anywhere: only a literal false makes it print
            $return_type = $statements_analyzer->node_data->getType($args[$return_offset]->value);
            if ($return_type === null || !$return_type->isFalse()) {
                return;
            }
        }

        if (!self::isInCliOnlyCode($statements_analyzer, $context)) {
            return;
        }

        foreach ($args as $offset => $arg) {
            if ($offset !== $return_offset && $arg->name === null && !$arg->unpack) {
                $arg->value->setAttribute(self::PRINTED_ATTRIBUTE, true);
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
}
