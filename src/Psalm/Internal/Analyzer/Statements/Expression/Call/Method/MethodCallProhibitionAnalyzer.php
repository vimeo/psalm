<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\CliOutput;
use Psalm\Internal\Codebase\PhpDeprecations;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\DeprecatedMethod;
use Psalm\Issue\InternalClass;
use Psalm\Issue\InternalMethod;
use Psalm\IssueBuffer;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function count;
use function in_array;
use function strtolower;

/**
 * @internal
 */
final class MethodCallProhibitionAnalyzer
{
    /**
     * @param  string[]     $suppressed_issues
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        MethodIdentifier $method_id,
        ?string $caller_identifier,
        CodeLocation $code_location,
        array $suppressed_issues,
    ): void {
        $codebase = $statements_analyzer->getCodebase();
        $codebase_methods = $codebase->methods;

        CliOutput::checkMethodCall($statements_analyzer, $context, $method_id, $code_location);
        self::checkBuiltinDeprecation($statements_analyzer, $method_id, $code_location, $suppressed_issues);

        $method_id = $codebase_methods->getDeclaringMethodId($method_id);

        if ($method_id === null) {
            return;
        }

        $storage = $codebase_methods->getStorage($method_id);

        if ($storage->deprecated) {
            IssueBuffer::maybeAdd(
                new DeprecatedMethod(
                    'The method ' . $codebase_methods->getCasedMethodId($method_id) .
                        ' has been marked as deprecated',
                    $code_location,
                    (string) $method_id,
                ),
                $suppressed_issues,
            );
        }

        if (!$context->collect_initializations
        ) {
            if (!NamespaceAnalyzer::isWithinAny($caller_identifier ?? "", $storage->internal)) {
                IssueBuffer::maybeAdd(
                    new InternalMethod(
                        'The method ' . $codebase_methods->getCasedMethodId($method_id)
                            . ' is internal to ' . InternalClass::listToPhrase($storage->internal)
                            . ' but called from ' . ($caller_identifier ?: 'root namespace'),
                        $code_location,
                        (string) $method_id,
                    ),
                    $suppressed_issues,
                );
            }
        }
    }

    /**
     * Reports a call of a builtin method a PHP version deprecates (see PhpDeprecations)
     *
     * @param  string[]     $suppressed_issues
     */
    public static function checkBuiltinDeprecation(
        StatementsAnalyzer $statements_analyzer,
        MethodIdentifier $method_id,
        CodeLocation $code_location,
        array $suppressed_issues,
        ?string $cased_method_name = null,
    ): void {
        $codebase_methods = $statements_analyzer->getCodebase()->methods;
        $declaring_method_id = $codebase_methods->getDeclaringMethodId($method_id);

        if ($declaring_method_id !== null) {
            $cased_method_id = $codebase_methods->getCasedMethodId($declaring_method_id);
        } elseif ($cased_method_name !== null) {
            // a method only the call map has
            $declaring_method_id = $method_id;
            $cased_method_id = $method_id->fq_class_name . '::' . $cased_method_name;
        } else {
            return;
        }

        $deprecation_message = PhpDeprecations::getMethodMessage((string) $declaring_method_id, $cased_method_id);

        if ($deprecation_message !== null) {
            IssueBuffer::maybeAdd(
                new DeprecatedMethod($deprecation_message, $code_location, (string) $declaring_method_id),
                $suppressed_issues,
            );
        }
    }

    /**
     * Reports the arguments of builtin methods and constructors that PHP deprecates, whatever PHP version is analysed
     *
     * @param list<PhpParser\Node\Arg> $args
     */
    public static function checkDeprecatedArguments(
        StatementsAnalyzer $statements_analyzer,
        MethodIdentifier $declaring_method_id,
        array $args,
        CodeLocation $code_location,
    ): void {
        $types = [];

        foreach ($args as $arg) {
            // a named or unpacked argument has no known position
            if ($arg->name !== null || $arg->unpack) {
                return;
            }

            $types[] = $statements_analyzer->node_data->getType($arg->value);
        }

        $count = count($types);
        $method_id = strtolower((string) $declaring_method_id);

        [$subject, $since, $replacement] = match (true) {
            $method_id === 'intlcalendar::roll' && ($types[1] ?? null)?->hasBool() === true
                => ['Passing a bool as the value of IntlCalendar::roll()', 8_01_00, '1 or -1'],
            $method_id === 'soapclient::__construct' && self::hasKey($types[1] ?? null, 'ssl_method')
                => ['The ssl_method option of SoapClient', 8_01_00, 'the SSL stream context options'],
            $method_id === 'reflectionproperty::setvalue' && $count === 1
                => ['Calling ReflectionProperty::setValue() with one argument', 8_03_00, 'null as the object'],
            $method_id === 'phar::setstub' && $count === 2
                => ['Passing a length to Phar::setStub()', 8_03_00, 'a stub string'],
            $method_id === 'sqlite3::enableexceptions' && ($count === 0 || ($types[0] ?? null)?->isFalse() === true)
                => ['Disabling the exceptions of SQLite3', 8_03_00, null],
            $method_id === 'intlcalendar::set' && $count > 2
                => [
                    'Calling IntlCalendar::set() with more than two arguments',
                    8_04_00,
                    'IntlCalendar::setDate() or IntlCalendar::setDateTime()',
                ],
            $method_id === 'intlgregoriancalendar::__construct' && $count > 2
                => [
                    'Constructing IntlGregorianCalendar with more than two arguments',
                    8_04_00,
                    'IntlGregorianCalendar::createFromDate() or IntlGregorianCalendar::createFromDateTime()',
                ],
            $method_id === 'dateperiod::__construct' && ($types[0] ?? null)?->isString() === true
                => [
                    'Constructing DatePeriod from an ISO 8601 string',
                    8_04_00,
                    'DatePeriod::createFromISO8601String()',
                ],
            $method_id === 'reflectionmethod::__construct' && $count === 1
                => [
                    'Constructing ReflectionMethod with one argument',
                    8_04_00,
                    'ReflectionMethod::createFromMethodName()',
                ],
            $method_id === 'soapserver::addfunction' && ($types[0] ?? null)?->isInt() === true
                => ['Passing an int to SoapServer::addFunction()', 8_04_00, 'the names of the functions'],
            $method_id === 'mysqli::store_result' && $count > 0
                => ['The mode argument of mysqli::store_result()', 8_04_00, null],
            $method_id === 'finfo::buffer' && $count > 2
                => ['The context argument of finfo::buffer()', 8_05_00, null],
            ($method_id === 'arrayobject::__construct'
                || $method_id === 'arrayiterator::__construct'
                || $method_id === 'arrayobject::exchangearray'
            ) && ($types[0] ?? null)?->hasObjectType() === true
                => ['Wrapping an object in ArrayObject or ArrayIterator', 8_05_00, 'get_object_vars()'],
            $method_id === 'mysqli_stmt::__construct' && $count === 1
                => [
                    'Calling mysqli_stmt::__construct() without the query argument',
                    8_06_00,
                    'mysqli::prepare()',
                ],
            default => [null, 0, null],
        };

        if ($subject !== null) {
            IssueBuffer::maybeAdd(
                new DeprecatedMethod(
                    PhpDeprecations::getDeprecatedSinceMessage($subject, $since, $replacement),
                    $code_location,
                    $method_id,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }
    }

    /**
     * Whether $type is an array that has the literal key $key
     *
     * @psalm-pure
     */
    private static function hasKey(?Union $type, string $key): bool
    {
        foreach ($type?->getAtomicTypes() ?? [] as $atomic) {
            if ($atomic instanceof TKeyedArray && isset($atomic->properties[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reports the methods of FFI PHP deprecates calling statically
     */
    public static function checkDeprecatedStaticCall(
        StatementsAnalyzer $statements_analyzer,
        MethodIdentifier $method_id,
        CodeLocation $code_location,
    ): void {
        if (strtolower($method_id->fq_class_name) === 'ffi'
            && in_array($method_id->method_name, ['cast', 'new', 'type'], true)
        ) {
            IssueBuffer::maybeAdd(
                new DeprecatedMethod(
                    PhpDeprecations::getDeprecatedSinceMessage(
                        'Calling FFI::' . $method_id->method_name . '() statically',
                        8_03_00,
                        null,
                    ) . ', call it on an FFI instance',
                    $code_location,
                    (string) $method_id,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }
    }
}
