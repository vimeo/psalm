<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\PhpDeprecations;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\DeprecatedFunction;
use Psalm\Issue\DeprecatedMethod;
use Psalm\Issue\ReservedWord;
use Psalm\IssueBuffer;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

use function count;
use function end;
use function explode;
use function in_array;
use function preg_match;
use function preg_split;
use function str_starts_with;
use function strtolower;
use function substr_count;

use const PREG_SPLIT_NO_EMPTY;

/**
 * The arguments of builtin functions that PHP deprecates, whatever PHP version is analysed (see PhpDeprecations)
 *
 * @internal
 */
final class DeprecatedArgumentsAnalyzer
{
    /** the parameters, by position, of the builtins that deprecate an object for an array */
    private const ARRAY_PARAMETERS = [
        'array_walk' => [0, 'the array argument of array_walk()'],
        'array_walk_recursive' => [0, 'the array argument of array_walk_recursive()'],
        'http_build_query' => [0, 'the data argument of http_build_query()'],
        'deflate_init' => [1, 'the options argument of deflate_init()'],
        'inflate_init' => [1, 'the options argument of inflate_init()'],
    ];

    /** the stream filters whose parameters can't be an object */
    private const ARRAY_PARAMETER_FILTERS = ['zlib.deflate', 'zlib.inflate', 'bzip2.compress', 'bzip2.decompress'];

    /** the functions opening a file whose path, the first argument, can be a php://filter URL */
    private const FILE_FUNCTIONS = ['fopen', 'file_get_contents', 'file_put_contents', 'file', 'readfile', 'copy'];

    /** the number of filters a php://filter URL can chain without the filter.max_filter_count context option */
    private const MAX_FILTERS = 16;

    /**
     * @param list<Arg> $args
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        array $args,
        CodeLocation $code_location,
    ): void {
        $function_id = strtolower($function_id);

        foreach (self::getMessages($statements_analyzer, $function_id, $args) as $message) {
            IssueBuffer::maybeAdd(
                new DeprecatedFunction($message, $code_location, $function_id),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        // the names PHP reserves (see DeprecatedSyntaxAnalyzer)
        $name_position = ['define' => 0, 'class_alias' => 1][$function_id] ?? null;
        $name_arg = $name_position === null ? null : $args[$name_position] ?? null;
        $name_type = $name_arg === null || $name_arg->name !== null || $name_arg->unpack
            ? null
            : $statements_analyzer->node_data->getType($name_arg->value);

        if ($name_type !== null && $name_type->isSingleStringLiteral()) {
            $name_parts = explode('\\', $name_type->getSingleStringLiteral()->value);
            $name = end($name_parts);

            if (in_array(strtolower($name), ['let', 'is'], true)
                || ($name === '_' && $function_id === 'define')
            ) {
                IssueBuffer::maybeAdd(
                    new ReservedWord(
                        PhpDeprecations::getDeprecatedSinceMessage(
                            ($function_id === 'define' ? 'Defining a constant named ' : 'Aliasing a class to ') . $name,
                            8_06_00,
                            null,
                        ) . ', rename it',
                        $code_location,
                        $name,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }
    }

    /**
     * Reports the arguments of `new $fq_class_name(...)` that PHP deprecates
     *
     * @param list<Arg> $args
     */
    public static function analyzeConstructorCall(
        StatementsAnalyzer $statements_analyzer,
        string $fq_class_name,
        array $args,
        CodeLocation $code_location,
    ): void {
        $methods = $statements_analyzer->getCodebase()->methods;
        $declaring_method_id = $methods->getDeclaringMethodId(new MethodIdentifier($fq_class_name, '__construct'));

        if ($declaring_method_id === null
            || strtolower((string) $declaring_method_id) !== 'mysqli_stmt::__construct'
            || count($args) > 1
        ) {
            return;
        }

        foreach ($args as $arg) {
            if ($arg->unpack || $arg->name?->toLowerString() === 'query') {
                return;
            }
        }

        IssueBuffer::maybeAdd(
            new DeprecatedMethod(
                PhpDeprecations::getDeprecatedSinceMessage(
                    'Calling mysqli_stmt::__construct() without the query argument',
                    8_06_00,
                    'mysqli::prepare()',
                ),
                $code_location,
                (string) $declaring_method_id,
            ),
            $statements_analyzer->getSuppressedIssues(),
        );
    }

    /**
     * @param list<Arg> $args
     * @return list<string>
     */
    private static function getMessages(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        array $args,
    ): array {
        $messages = [];
        $types = [];

        foreach ($args as $arg) {
            // a named or unpacked argument has no known position
            if ($arg->name !== null || $arg->unpack) {
                return [];
            }

            $types[] = $statements_analyzer->node_data->getType($arg->value);
        }

        if (isset(self::ARRAY_PARAMETERS[$function_id])) {
            [$position, $argument] = self::ARRAY_PARAMETERS[$function_id];

            if (self::canBeObject($types[$position] ?? null)) {
                $messages[] = self::getObjectMessage($argument);
            }
        }

        switch ($function_id) {
            case 'mb_convert_variables':
                for ($position = 2, $count = count($types); $position < $count; $position++) {
                    if (self::canBeObject($types[$position])) {
                        $messages[] = self::getObjectMessage('the vars argument of mb_convert_variables()');

                        break;
                    }
                }

                break;

            case 'stream_filter_append':
            case 'stream_filter_prepend':
                if (self::canBeObject($types[3] ?? null)
                    && self::isAnyOf($types[1] ?? null, self::ARRAY_PARAMETER_FILTERS)
                ) {
                    $messages[] = self::getObjectMessage('the parameters of the zlib and bzip2 stream filters');
                }

                break;

            case 'define':
                if (count($types) > 2) {
                    $messages[] = PhpDeprecations::getDeprecatedSinceMessage(
                        'The case_insensitive argument of define()',
                        8_06_00,
                        null,
                    ) . ', leave it out';
                }

                break;

            case 'is_a':
            case 'is_subclass_of':
                $allow_string = $types[2] ?? null;
                $allows_strings = $allow_string === null
                    ? $function_id === 'is_subclass_of'
                    : !$allow_string->isFalse();

                if (!$allows_strings && self::canBeString($types[0] ?? null)) {
                    $messages[] = PhpDeprecations::getDeprecatedSinceMessage(
                        'Calling ' . $function_id . '() with a class name when allow_string is false',
                        8_06_00,
                        null,
                    ) . ', check class_exists() first and pass true for allow_string';
                }

                break;

            case 'session_set_save_handler':
                if (self::lacksSessionIdMethods($statements_analyzer, $types[0] ?? null)) {
                    $messages[] = PhpDeprecations::getDeprecatedSinceMessage(
                        'A session handler without the create_sid() and validateId() methods',
                        8_06_00,
                        null,
                    ) . ', implement SessionIdInterface and SessionUpdateTimestampHandlerInterface';
                }

                break;
        }

        if (in_array($function_id, self::FILE_FUNCTIONS, true)) {
            foreach ($function_id === 'copy' ? [0, 1] : [0] as $position) {
                if (self::chainsTooManyFilters($types[$position] ?? null)) {
                    $messages[] = PhpDeprecations::getDeprecatedSinceMessage(
                        'A php://filter URL chaining more than ' . self::MAX_FILTERS . ' filters',
                        8_06_00,
                        null,
                    ) . ', append them with stream_filter_append() or raise the filter.max_filter_count context option';
                }
            }
        }

        return $messages;
    }

    /**
     * @psalm-pure
     */
    private static function getObjectMessage(string $argument): string
    {
        return PhpDeprecations::getDeprecatedSinceMessage(
            'Passing an object as ' . $argument,
            8_06_00,
            'get_object_vars()',
        );
    }

    /**
     * @psalm-pure
     */
    private static function canBeObject(?Union $type): bool
    {
        foreach ($type?->getAtomicTypes() ?? [] as $atomic) {
            if ($atomic instanceof TNamedObject || $atomic instanceof TObject) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-pure
     */
    private static function canBeString(?Union $type): bool
    {
        foreach ($type?->getAtomicTypes() ?? [] as $atomic) {
            if ($atomic instanceof TString) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether $type is one of the literal strings $values
     *
     * @param list<string> $values
     * @psalm-pure
     */
    private static function isAnyOf(?Union $type, array $values): bool
    {
        if ($type === null || !$type->allStringLiterals()) {
            return false;
        }

        foreach ($type->getLiteralStrings() as $literal) {
            if (!in_array(strtolower($literal->value), $values, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether $type is an object of a class that doesn't have both create_sid() and validateId(): SessionHandler has
     * both as of PHP 8.6
     */
    private static function lacksSessionIdMethods(StatementsAnalyzer $statements_analyzer, ?Union $type): bool
    {
        if ($type === null || !$type->isSingle()) {
            return false;
        }

        $object = $type->getSingleAtomic();

        if (!$object instanceof TNamedObject || $object->extra_types !== []) {
            return false;
        }

        $codebase = $statements_analyzer->getCodebase();

        return $codebase->classlikes->classExists($object->value)
            && !$codebase->classlikes->interfaceExists($object->value)
            && strtolower($object->value) !== 'sessionhandler'
            && !$codebase->classExtends($object->value, 'SessionHandler')
            && (!$codebase->methodExists($object->value . '::create_sid')
                || !$codebase->methodExists($object->value . '::validateid'));
    }

    /**
     * Whether $type is a php://filter URL chaining more filters than PHP allows by default
     *
     * @psalm-pure
     */
    private static function chainsTooManyFilters(?Union $type): bool
    {
        if ($type === null || !$type->isSingleStringLiteral()) {
            return false;
        }

        $url = $type->getSingleStringLiteral()->value;

        if (!str_starts_with(strtolower($url), 'php://filter/')
            || !preg_match('~^php://filter/(.*)/resource=~is', $url, $matches)
        ) {
            return false;
        }

        $filters = 0;

        $parts = preg_split('~/~', $matches[1] ?? '', -1, PREG_SPLIT_NO_EMPTY);

        // `read=a|b`, `write=c`, or filters for both
        foreach ($parts === false ? [] : $parts as $part) {
            $filters += substr_count($part, '|') + 1;
        }

        return $filters > self::MAX_FILTERS;
    }
}
