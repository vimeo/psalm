<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\ArithmeticOpAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\IncludeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\PhpDeprecations;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\DeprecatedFunction;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\PossibleRawObjectIteration;
use Psalm\Issue\RawObjectIteration;
use Psalm\Issue\RedundantFunctionCall;
use Psalm\Issue\RedundantFunctionCallGivenDocblockType;
use Psalm\Issue\ReservedWord;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualArray;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Node\Scalar\VirtualString;
use Psalm\Node\VirtualArrayItem;
use Psalm\Type;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClosedResource;
use Psalm\Type\Atomic\TDependentGetClass;
use Psalm\Type\Atomic\TDependentGetDebugType;
use Psalm\Type\Atomic\TDependentGetType;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function array_map;
use function array_pop;
use function count;
use function explode;
use function extension_loaded;
use function in_array;
use function is_numeric;
use function is_string;
use function ltrim;
use function preg_match;
use function preg_split;
use function str_starts_with;
use function strlen;
use function strpos;
use function strtolower;
use function substr_count;

use const EXTR_OVERWRITE;
use const EXTR_SKIP;
use const E_USER_ERROR;
use const PREG_SPLIT_NO_EMPTY;

/**
 * @internal
 */
final class NamedFunctionCallHandler
{
    /** the stream filters whose parameters can't be an object */
    private const ARRAY_PARAMETER_FILTERS = ['zlib.deflate', 'zlib.inflate', 'bzip2.compress', 'bzip2.decompress'];

    /** the number of filters a php://filter URL can chain without the filter.max_filter_count context option */
    private const MAX_FILTERS = 16;

    /** the pgsql functions using the last connection, by the number of arguments they then take */
    private const PGSQL_LAST_CONNECTION_ARGUMENTS = [
        'pg_client_encoding' => 0, 'pg_clientencoding' => 0, 'pg_close' => 0, 'pg_dbname' => 0, 'pg_end_copy' => 0,
        'pg_errormessage' => 0, 'pg_host' => 0, 'pg_last_error' => 0, 'pg_lo_create' => 0, 'pg_locreate' => 0,
        'pg_options' => 0, 'pg_ping' => 0, 'pg_port' => 0, 'pg_tty' => 0, 'pg_untrace' => 0, 'pg_version' => 0,
        'pg_escape_bytea' => 1, 'pg_escape_identifier' => 1, 'pg_escape_literal' => 1, 'pg_escape_string' => 1,
        'pg_exec' => 1, 'pg_query' => 1, 'pg_lo_unlink' => 1, 'pg_lounlink' => 1, 'pg_parameter_status' => 1,
        'pg_put_line' => 1, 'pg_set_client_encoding' => 1, 'pg_setclientencoding' => 1, 'pg_set_error_verbosity' => 1,
        'pg_execute' => 2, 'pg_lo_export' => 2, 'pg_loexport' => 2, 'pg_lo_open' => 2, 'pg_loopen' => 2,
        'pg_prepare' => 2, 'pg_query_params' => 2,
    ];

    /** the mbstring functions, by the positions of their parameters naming encodings, whatever their name */
    private const MB_ENCODING_POSITIONS = [
        'mb_internal_encoding' => [0],
        'mb_http_output' => [0],
        'mb_detect_order' => [0],
        'mb_preferred_mime_name' => [0],
        'mb_str_split' => [2],
        'mb_strlen' => [1],
        'mb_strpos' => [3],
        'mb_strrpos' => [3],
        'mb_stripos' => [3],
        'mb_strripos' => [3],
        'mb_strstr' => [3],
        'mb_strrchr' => [3],
        'mb_stristr' => [3],
        'mb_strrichr' => [3],
        'mb_substr_count' => [2],
        'mb_substr' => [3],
        'mb_strcut' => [3],
        'mb_strwidth' => [1],
        'mb_strimwidth' => [4],
        'mb_convert_encoding' => [1, 2],
        'mb_convert_case' => [2],
        'mb_strtoupper' => [1],
        'mb_strtolower' => [1],
        'mb_ucfirst' => [1],
        'mb_lcfirst' => [1],
        'mb_trim' => [2],
        'mb_ltrim' => [2],
        'mb_rtrim' => [2],
        'mb_detect_encoding' => [1],
        'mb_encoding_aliases' => [0],
        'mb_encode_mimeheader' => [1],
        'mb_convert_kana' => [2],
        'mb_convert_variables' => [0, 1],
        'mb_encode_numericentity' => [2],
        'mb_decode_numericentity' => [2],
        'mb_check_encoding' => [1],
        'mb_scrub' => [1],
        'mb_ord' => [1],
        'mb_chr' => [1],
        'mb_str_pad' => [4],
        'mb_regex_encoding' => [0],    ];

    /** the functions reading CSV, by the position of their escape parameter */
    private const CSV_ESCAPE_POSITIONS = ['fgetcsv' => 4, 'fputcsv' => 4, 'str_getcsv' => 3];

    /**
     * the functions deprecated when called with more than ('>') or exactly ('===') this number of arguments,
     * by what is deprecated, since when, its replacement and what to append to the message
     *
     * @var array<lowercase-string, array{'>'|'===', int, string, int, ?string, string}>
     */
    private const DEPRECATED_CALL_SHAPES = [
        'mb_check_encoding' => ['===', 0, 'Calling mb_check_encoding() without arguments', 8_01_00, null, ''],
        'mysqli_get_client_info' => [
            '>', 0, 'Passing a connection to mysqli_get_client_info()', 8_01_00, null, ', leave it out',
        ],
        'imagepolygon' => ['>', 3, 'The num_points argument of imagepolygon()', 8_01_00, null, ', leave it out'],
        'imageopenpolygon' => [
            '>', 3, 'The num_points argument of imageopenpolygon()', 8_01_00, null, ', leave it out',
        ],
        'imagefilledpolygon' => [
            '>', 3, 'The num_points argument of imagefilledpolygon()', 8_01_00, null, ', leave it out',
        ],
        'get_class' => [
            '===', 0, 'Calling get_class() without arguments', 8_03_00, 'self::class or static::class', '',
        ],
        'get_parent_class' => [
            '===', 0, 'Calling get_parent_class() without arguments', 8_03_00, 'get_parent_class($this)', '',
        ],
        'ldap_connect' => [
            '>', 1, 'Calling ldap_connect() with more than one argument', 8_03_00, null, ', pass an LDAP URI',
        ],
        'pg_fetch_result' => [
            '===', 2, 'Calling pg_fetch_result() with two arguments', 8_04_00, null, ', pass null as the row',
        ],
        'pg_field_prtlen' => [
            '===', 2, 'Calling pg_field_prtlen() with two arguments', 8_04_00, null, ', pass null as the row',
        ],
        'pg_field_is_null' => [
            '===', 2, 'Calling pg_field_is_null() with two arguments', 8_04_00, null, ', pass null as the row',
        ],
        'intlcal_set' => [
            '>', 3, 'Calling intlcal_set() with more than three arguments', 8_04_00,
            'IntlCalendar::setDate() or IntlCalendar::setDateTime()', '',
        ],
        'intlgregcal_create_instance' => [
            '>', 2, 'Calling intlgregcal_create_instance() with more than two arguments', 8_04_00,
            'IntlGregorianCalendar::createFromDate() or IntlGregorianCalendar::createFromDateTime()', '',
        ],
        'ldap_exop' => ['>', 4, 'Calling ldap_exop() with more than four arguments', 8_04_00, 'ldap_exop_sync()', ''],
        'mysqli_store_result' => [
            '>', 1, 'The mode argument of mysqli_store_result()', 8_04_00, null, ', leave it out',
        ],
        'stream_context_set_option' => [
            '===', 2, 'Calling stream_context_set_option() with an array of options', 8_04_00,
            'stream_context_set_options()', '',
        ],
        'openssl_pkey_derive' => [
            '>', 2, 'The key_length argument of openssl_pkey_derive()', 8_05_00, null, ', leave it out',
        ],
        'finfo_buffer' => ['>', 3, 'The context argument of finfo_buffer()', 8_05_00, null, ', leave it out'],
        'define' => ['>', 2, 'The case_insensitive argument of define()', 8_06_00, null, ', leave it out'],
    ];

    /**
     * @param lowercase-string $function_id
     */
    public static function handle(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Expr\FuncCall $real_stmt,
        PhpParser\Node\Name $function_name,
        string $function_id,
        Context $context,
    ): void {
        if ($function_id === 'get_class'
            || $function_id === 'gettype'
            || $function_id === 'get_debug_type'
        ) {
            if (!$stmt->isFirstClassCallable()) {
                self::checkDeprecatedArguments($statements_analyzer, $stmt, $function_id);
            }

            self::handleDependentTypeFunction(
                $statements_analyzer,
                $stmt,
                $real_stmt,
                $function_id,
                $context,
            );

            return;
        }

        if ($stmt->isFirstClassCallable()) {
            return;
        }

        self::checkDeprecatedArguments($statements_analyzer, $stmt, $function_id);

        $first_arg = $stmt->getArgs()[0] ?? null;

        if ($function_id === 'method_exists') {
            $second_arg = $stmt->getArgs()[1] ?? null;

            if ($first_arg
                && $first_arg->value instanceof PhpParser\Node\Expr\Variable
                && $second_arg
                && $second_arg->value instanceof PhpParser\Node\Scalar\String_
            ) {
                // do nothing
            } else {
                $context->check_methods = false;
            }

            return;
        }

        if ($function_id === 'class_alias') {
            $alias_name = self::getClassNameOfArgument($stmt->getArgs()[1] ?? null);
            $alias_name_parts = explode('\\', (string) $alias_name);
            $alias_message = PhpDeprecations::getClassLikeNameMessage('an alias', array_pop($alias_name_parts), false);

            if ($alias_message !== null) {
                IssueBuffer::maybeAdd(
                    new ReservedWord(
                        $alias_message,
                        new CodeLocation($statements_analyzer, $stmt),
                        (string) $alias_name,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }

        if ($function_id === 'class_alias'
            || $function_id === 'class_exists'
            || $function_id === 'interface_exists'
            || $function_id === 'enum_exists'
        ) {
            self::handleClassLikeDeclarationCheck($codebase, $stmt, $function_id, $context);

            return;
        }

        if (in_array($function_id, ['is_file', 'file_exists']) && $first_arg) {
            $var_id = ExpressionIdentifier::getExtendedVarId($first_arg->value, null)
                ?? IncludeAnalyzer::getConstantPhantomFileId($first_arg->value);

            if ($var_id) {
                $context->phantom_files[$var_id] = true;

                if ($context->inside_negation) {
                    $context->negated_phantom_files[$var_id] = true;
                } else {
                    unset($context->negated_phantom_files[$var_id]);
                }

                return;
            }

            // literal string or (magic) const in file path
            $codebase = $statements_analyzer->getCodebase();
            $config = $codebase->config;
            $path_to_file = IncludeAnalyzer::getPathTo(
                $first_arg->value,
                $statements_analyzer->node_data,
                $statements_analyzer,
                $statements_analyzer->getFileName(),
                $config,
            );

            if ($path_to_file) {
                $normalized_path = IncludeAnalyzer::normalizeFilePath($path_to_file);
                $context->phantom_files[$normalized_path] = true;

                if ($context->inside_negation) {
                    $context->negated_phantom_files[$normalized_path] = true;
                } else {
                    unset($context->negated_phantom_files[$normalized_path]);
                }
            }

            return;
        }

        if ($function_id === 'extension_loaded') {
            if ($first_arg
                && $first_arg->value instanceof PhpParser\Node\Scalar\String_
            ) {
                if (@extension_loaded($first_arg->value->value)) {
                    // do nothing
                } else {
                    $context->check_classes = false;
                }
            }

            return;
        }

        if ($function_id === 'function_exists') {
            if ($first_arg && $first_arg->value instanceof PhpParser\Node\Scalar\String_) {
                $context->phantom_functions[strtolower(ltrim($first_arg->value->value, '\\'))] = true;
            } else {
                $context->check_functions = false;
            }

            return;
        }

        if ($function_id === 'is_callable') {
            $context->check_methods = false;
            $context->check_functions = false;
            return;
        }

        if ($function_id === 'defined') {
            if ($first_arg) {
                $fq_const_name = ConstFetchAnalyzer::getConstName(
                    $first_arg->value,
                    $statements_analyzer->node_data,
                    $codebase,
                    $statements_analyzer->getAliases(),
                );

                if ($fq_const_name !== null) {
                    $const_type = ConstFetchAnalyzer::getConstType(
                        $statements_analyzer,
                        $fq_const_name,
                        true,
                        $context,
                    );

                    if (!$const_type) {
                        ConstFetchAnalyzer::setConstType(
                            $statements_analyzer,
                            $fq_const_name,
                            Type::getMixed(),
                            $context,
                        );

                        // `if (!defined('X')) { exit; }` defines X for the code after it, but says nothing
                        // about the other constants
                        if (!$context->inside_negation) {
                            $context->check_consts = false;
                        }
                    }
                } else {
                    $context->check_consts = false;
                }
            } else {
                $context->check_consts = false;
            }
            return;
        }

        if ($function_id === 'extract') {
            self::handleExtract($statements_analyzer, $codebase, $stmt, $context);
            return;
        }

        if ($function_id === 'compact') {
            $all_args_string_literals = true;
            $new_items = [];

            foreach ($stmt->getArgs() as $arg) {
                $arg_type = $statements_analyzer->node_data->getType($arg->value);

                if (!$arg_type || !$arg_type->isSingleStringLiteral()) {
                    $all_args_string_literals = false;
                    break;
                }

                $var_name = $arg_type->getSingleStringLiteral()->value;

                $new_items[] = new VirtualArrayItem(
                    new VirtualVariable($var_name, $arg->value->getAttributes()),
                    new VirtualString($var_name, $arg->value->getAttributes()),
                    false,
                    $arg->getAttributes(),
                );
            }

            if ($all_args_string_literals) {
                $arr = new VirtualArray($new_items, $stmt->getAttributes());
                $old_node_data = $statements_analyzer->node_data;
                $statements_analyzer->node_data = clone $statements_analyzer->node_data;

                ExpressionAnalyzer::analyze($statements_analyzer, $arr, $context);

                $arr_type = $statements_analyzer->node_data->getType($arr);

                $statements_analyzer->node_data = $old_node_data;

                if ($arr_type) {
                    $statements_analyzer->node_data->setType($stmt, $arr_type);
                }
            }

            return;
        }

        if ($function_id === 'session_unset') {
            $context->remove('$_SESSION');
            $context->vars_in_scope['$_SESSION'] = Type::getEmptyArray();
            $context->vars_possibly_in_scope['$_SESSION'] = true;

            return;
        }

        if ($function_id === 'func_get_args') {
            $source = $statements_analyzer->getSource();

            if ($source instanceof FunctionLikeAnalyzer) {
                if ($statements_analyzer->data_flow_graph) {
                    foreach ($source->param_nodes as $param_node) {
                        $statements_analyzer->data_flow_graph->addPath(
                            $param_node,
                            DataFlowNode::getForVariableUse(),
                            'variable-use',
                        );
                    }
                }
            }

            return;
        }

        if ($function_id === 'var_dump'
            || $function_id === 'shell_exec'
        ) {
            IssueBuffer::maybeAdd(
                new ForbiddenCode(
                    'Unsafe ' . $function_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if (isset($codebase->config->forbidden_functions[$function_id])) {
            IssueBuffer::maybeAdd(
                new ForbiddenCode(
                    'You have forbidden the use of ' . $function_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );

            return;
        }

        if ($function_id === 'define') {
            if ($first_arg) {
                $fq_const_name = ConstFetchAnalyzer::getConstName(
                    $first_arg->value,
                    $statements_analyzer->node_data,
                    $codebase,
                    $statements_analyzer->getAliases(),
                );

                if ($fq_const_name !== null) {
                    ConstFetchAnalyzer::checkDeprecatedName(
                        $statements_analyzer,
                        $fq_const_name,
                        new CodeLocation($statements_analyzer, $stmt),
                    );
                }

                if ($fq_const_name !== null && isset($stmt->getArgs()[1])) {
                    $second_arg = $stmt->getArgs()[1];
                    $was_in_call = $context->inside_call;
                    $context->inside_call = true;
                    ExpressionAnalyzer::analyze($statements_analyzer, $second_arg->value, $context);
                    $context->inside_call = $was_in_call;

                    ConstFetchAnalyzer::setConstType(
                        $statements_analyzer,
                        $fq_const_name,
                        $statements_analyzer->node_data->getType($second_arg->value) ?? Type::getMixed(),
                        $context,
                    );
                }
            } else {
                $context->check_consts = false;
            }

            return;
        }

        if ($function_id === 'constant') {
            if ($first_arg) {
                $fq_const_name = ConstFetchAnalyzer::getConstName(
                    $first_arg->value,
                    $statements_analyzer->node_data,
                    $codebase,
                    $statements_analyzer->getAliases(),
                );

                if ($fq_const_name !== null) {
                    $const_type = ConstFetchAnalyzer::getConstType(
                        $statements_analyzer,
                        $fq_const_name,
                        true,
                        $context,
                    );

                    if ($const_type) {
                        $statements_analyzer->node_data->setType($real_stmt, $const_type);
                    }
                }
            } else {
                $context->check_consts = false;
            }
        }

        if ($first_arg
            && $function_id
            && str_starts_with($function_id, 'is_')
            && $function_id !== 'is_a'
            && !$context->inside_negation
            && !$context->inside_assert
        ) {
            $stmt_assertions = $statements_analyzer->node_data->getAssertions($stmt);

            $anded_assertions = $stmt_assertions ?? AssertionFinder::processFunctionCall(
                $stmt,
                $context->self,
                $statements_analyzer,
                $codebase,
                $context->inside_negation,
            );

            $changed_vars = [];

            foreach ($anded_assertions as $assertions) {
                $referenced_var_ids = array_map(
                    static fn(array $_): bool => true,
                    $assertions,
                );

                Reconciler::reconcileKeyedTypes(
                    $assertions,
                    $assertions,
                    $context->vars_in_scope,
                    $context->references_in_scope,
                    $changed_vars,
                    $referenced_var_ids,
                    $statements_analyzer,
                    [],
                    $context->inside_loop,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                );
            }

            return;
        }

        if ($first_arg && ($function_id === 'array_values' || $function_id === 'ksort')) {
            $first_arg_type = $statements_analyzer->node_data->getType($first_arg->value);

            if ($first_arg_type
                && UnionTypeComparator::isContainedBy(
                    $codebase,
                    $first_arg_type,
                    Type::getList(),
                )
            ) {
                if ($first_arg_type->from_docblock) {
                    IssueBuffer::maybeAdd(
                        new RedundantFunctionCallGivenDocblockType(
                            "The call to $function_id is unnecessary given the list docblock type $first_arg_type",
                            new CodeLocation($statements_analyzer, $function_name),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new RedundantFunctionCall(
                            "The call to $function_id is unnecessary, $first_arg_type is already a list",
                            new CodeLocation($statements_analyzer, $function_name),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            }
        }
        if ($first_arg && $function_id === 'strtolower') {
            $first_arg_type = $statements_analyzer->node_data->getType($first_arg->value);
            $comparison_result = new TypeComparisonResult();

            if ($first_arg_type
                && UnionTypeComparator::isContainedBy(
                    $codebase,
                    $first_arg_type,
                    new Union([new TLowercaseString()]),
                    false,
                    false,
                    $comparison_result,
                )
                && !$comparison_result->to_string_cast
            ) {
                if ($first_arg_type->from_docblock) {
                    IssueBuffer::maybeAdd(
                        new RedundantFunctionCallGivenDocblockType(
                            'The call to strtolower is unnecessary given the docblock type',
                            new CodeLocation($statements_analyzer, $function_name),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new RedundantFunctionCall(
                            'The call to strtolower is unnecessary',
                            new CodeLocation($statements_analyzer, $function_name),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            }
        }

        if ($first_arg
            && ($function_id === 'array_walk'
                || $function_id === 'array_walk_recursive'
            )
        ) {
            $first_arg_type = $statements_analyzer->node_data->getType($first_arg->value);

            if ($first_arg_type && $first_arg_type->hasObjectType()) {
                self::reportDeprecatedObjectArgument(
                    $statements_analyzer,
                    $stmt,
                    $function_id,
                    'the array argument of ' . $function_id . '()',
                );

                if ($first_arg_type->isSingle()) {
                    IssueBuffer::maybeAdd(
                        new RawObjectIteration(
                            'Possibly undesired iteration over object properties',
                            new CodeLocation($statements_analyzer, $function_name),
                        ),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new PossibleRawObjectIteration(
                            'Possibly undesired iteration over object properties',
                            new CodeLocation($statements_analyzer, $function_name),
                        ),
                    );
                }
            }
        }

        if ($first_arg
            && $function_id === 'is_a'
            // assertion reconsiler already emits relevant (but different) issues
            && !$context->inside_conditional
        ) {
            $first_arg_type = $statements_analyzer->node_data->getType($first_arg->value);

            if ($first_arg_type && $first_arg_type->isString()) {
                $third_arg = $stmt->getArgs()[2] ?? null;
                if ($third_arg) {
                    $third_arg_type = $statements_analyzer->node_data->getType($third_arg->value);
                } else {
                    $third_arg_type = Type::getFalse();
                }

                if ($third_arg_type
                    && $third_arg_type->isSingle()
                    && $third_arg_type->isFalse()
                ) {
                    if ($first_arg_type->from_docblock) {
                        IssueBuffer::maybeAdd(new RedundantFunctionCallGivenDocblockType(
                            'Call to is_a always return false when first argument is string '
                            . 'unless third argument is true',
                            new CodeLocation($statements_analyzer, $function_name),
                        ));
                    } else {
                        IssueBuffer::maybeAdd(new RedundantFunctionCall(
                            'Call to is_a always return false when first argument is string '
                            . 'unless third argument is true',
                            new CodeLocation($statements_analyzer, $function_name),
                        ));
                    }
                }
            }
        }
    }

    /**
     * The flag given to extract() when it is EXTR_OVERWRITE or EXTR_SKIP, false otherwise.
     *
     * @param array<int, PhpParser\Node\Arg> $args
     * @psalm-capabilities read-props
     */
    private static function getExtractFlag(StatementsAnalyzer $statements_analyzer, array $args): int|false
    {
        if (!isset($args[1])) {
            return EXTR_OVERWRITE;
        }

        $flags_type = $statements_analyzer->node_data->getType($args[1]->value);

        if (!$flags_type || !$flags_type->isSingleIntLiteral()) {
            return false;
        }

        // @todo add support for other flags
        return match ($flags_type->getSingleIntLiteral()->value) {
            EXTR_SKIP => EXTR_SKIP,
            EXTR_OVERWRITE => EXTR_OVERWRITE,
            default => false,
        };
    }

    /**
     * The sealed array given to extract() when the analysis knows the variable extract() assigns for each of its
     * keys, null otherwise.
     *
     * @param array<int, PhpParser\Node\Arg> $args
     * @psalm-capabilities read-props
     */
    public static function getExtractedArray(StatementsAnalyzer $statements_analyzer, array $args): ?TKeyedArray
    {
        if (!isset($args[0])
            || $args[0]->unpack
            || ($args[0]->name !== null && $args[0]->name->name !== 'array')
            || (isset($args[1]) && $args[1]->name !== null && $args[1]->name->name !== 'flags')
            || self::getExtractFlag($statements_analyzer, $args) === false
            || !($array_type = $statements_analyzer->node_data->getType($args[0]->value))
            || !$array_type->isSingle()
        ) {
            return null;
        }

        $array_atomic = $array_type->getSingleAtomic();

        return $array_atomic instanceof TKeyedArray && $array_atomic->fallback_params === null
            ? $array_atomic
            : null;
    }

    private static function handleExtract(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\FuncCall $stmt,
        Context $context,
    ): void {
        $args = $stmt->getArgs();
        $flag_value = self::getExtractFlag($statements_analyzer, $args);

        $is_unsealed = true;
        $validated_var_ids = [];
        if ($flag_value !== false
            && isset($args[0])
            && !$args[0]->unpack
            && ($array_type_union = $statements_analyzer->node_data->getType($args[0]->value))
            && $array_type_union->isSingle()
        ) {
            foreach ($array_type_union->getAtomicTypes() as $array_type) {
                if ($array_type instanceof TKeyedArray) {
                    foreach ($array_type->properties as $key => $type) {
                        // variables must start with letters or underscore
                        if ($key === '' || is_numeric($key) || preg_match('/^[A-Za-z_]/', $key) !== 1) {
                            continue;
                        }

                        $var_id = '$' . $key;
                        $validated_var_ids[] = $var_id;

                        // the variable gets what the array holds under its key
                        $key_type = Type::getString($key);
                        ArrayFetchAnalyzer::taintArrayFetch(
                            $statements_analyzer,
                            $args[0]->value,
                            $var_id,
                            $type,
                            $key_type,
                            null,
                            $array_type_union,
                        );

                        if (isset($context->vars_in_scope[$var_id]) && $flag_value === EXTR_SKIP) {
                            $existing_type = $context->vars_in_scope[$var_id];

                            if (!$existing_type->possibly_undefined) {
                                continue;
                            }

                            // it is skipped where it is set, extracted where it is not
                            $type = Type::combineUnionTypes(
                                $existing_type->setPossiblyUndefined(false, false),
                                $type,
                                $codebase,
                            );
                        } elseif (!isset($context->vars_in_scope[$var_id]) && $type->possibly_undefined === true) {
                            $context->possibly_assigned_var_ids[$var_id] = true;
                        } elseif (isset($context->vars_in_scope[$var_id]) && $type->possibly_undefined === true) {
                            $type = Type::combineUnionTypes(
                                $context->vars_in_scope[$var_id],
                                $type,
                                $codebase,
                                false,
                                true,
                                500,
                                false,
                            );
                        }

                        if (isset($context->vars_in_scope[$var_id])) {
                            $context->removeDescendents(
                                $var_id,
                                $context->vars_in_scope[$var_id],
                                $type,
                                $statements_analyzer,
                            );
                        }

                        $context->vars_in_scope[$var_id] = $type;
                        $context->assigned_var_ids[$var_id] = (int) $stmt->getAttribute('startFilePos');
                    }

                    if (!isset($array_type->fallback_params)) {
                        $is_unsealed = false;
                    }
                }
            }
        }

        if ($flag_value === EXTR_OVERWRITE && $is_unsealed === false) {
            return;
        }

        if ($flag_value === EXTR_SKIP && $is_unsealed === false) {
            return;
        }

        $context->check_variables = false;

        if ($flag_value === EXTR_SKIP) {
            return;
        }

        foreach ($context->vars_in_scope as $var_id => $_) {
            if ($var_id === '$this' || strpos($var_id, '[') || strpos($var_id, '>')) {
                continue;
            }

            if (in_array($var_id, $validated_var_ids, true)) {
                continue;
            }

            $mixed_type = new Union([new TMixed()], [
                'parent_nodes' => $context->vars_in_scope[$var_id]->parent_nodes,
            ]);

            $context->removeDescendents($var_id, $context->vars_in_scope[$var_id], $mixed_type);
            $context->vars_in_scope[$var_id] = $mixed_type;
            $context->assigned_var_ids[$var_id] = (int) $stmt->getAttribute('startFilePos');
            $context->possibly_assigned_var_ids[$var_id] = true;
        }
    }

    private static function handleDependentTypeFunction(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        PhpParser\Node\Expr\FuncCall $real_stmt,
        string $function_id,
        Context $context,
    ): void {
        $first_arg = $stmt->getArgs()[0] ?? null;

        if ($first_arg) {
            $var = $first_arg->value;

            if ($var instanceof PhpParser\Node\Expr\Variable
                && is_string($var->name)
            ) {
                $var_id = '$' . $var->name;

                if (isset($context->vars_in_scope[$var_id])) {
                    if (!$context->vars_in_scope[$var_id]->hasTemplate()) {
                        if ($function_id === 'get_class') {
                            $atomic_type = new TDependentGetClass(
                                $var_id,
                                $context->vars_in_scope[$var_id]->hasMixed()
                                    ? Type::getObject()
                                    : $context->vars_in_scope[$var_id],
                            );
                        } elseif ($function_id === 'gettype') {
                            $atomic_type = new TDependentGetType($var_id);
                        } else {
                            $atomic_type = new TDependentGetDebugType($var_id);
                        }

                        $statements_analyzer->node_data->setType($real_stmt, new Union([$atomic_type]));

                        return;
                    }
                }
            }

            if (($var_type = $statements_analyzer->node_data->getType($var))
                && ($function_id === 'get_class'
                    || $function_id === 'get_debug_type'
                )
            ) {
                $class_string_types = [];

                foreach ($var_type->getAtomicTypes() as $class_type) {
                    if ($class_type instanceof TNamedObject) {
                        $class_string_types[] = new TClassString($class_type->value, $class_type);
                    } elseif ($class_type instanceof TTemplateParam
                        && $class_type->as->isSingle()
                    ) {
                        $as_atomic_type = $class_type->as->getSingleAtomic();

                        if ($as_atomic_type instanceof TObject) {
                            $class_string_types[] = new TTemplateParamClass(
                                $class_type->param_name,
                                'object',
                                null,
                                $class_type->defining_class,
                            );
                        } elseif ($as_atomic_type instanceof TNamedObject) {
                            $class_string_types[] = new TTemplateParamClass(
                                $class_type->param_name,
                                $as_atomic_type->value,
                                $as_atomic_type,
                                $class_type->defining_class,
                            );
                        }
                    } elseif ($function_id === 'get_class') {
                        $class_string_types[] = new TClassString();
                    } else {
                        if ($class_type instanceof TInt) {
                            $class_string_types[] = Type::getAtomicStringFromLiteral('int');
                        } elseif ($class_type instanceof TString) {
                            $class_string_types[] = Type::getAtomicStringFromLiteral('string');
                        } elseif ($class_type instanceof TFloat) {
                            $class_string_types[] = Type::getAtomicStringFromLiteral('float');
                        } elseif ($class_type instanceof TBool) {
                            $class_string_types[] = Type::getAtomicStringFromLiteral('bool');
                        } elseif ($class_type instanceof TClosedResource) {
                            $class_string_types[] = Type::getAtomicStringFromLiteral('resource (closed)');
                        } elseif ($class_type instanceof TNull) {
                            $class_string_types[] = Type::getAtomicStringFromLiteral('null');
                        } else {
                            $class_string_types[] = new TString();
                        }
                    }
                }

                if ($class_string_types) {
                    $statements_analyzer->node_data->setType($real_stmt, new Union($class_string_types));
                }
            }
        } elseif ($function_id === 'get_class'
            && ($get_class_name = $statements_analyzer->getFQCLN())
        ) {
            $statements_analyzer->node_data->setType(
                $real_stmt,
                new Union([
                    new TClassString(
                        $get_class_name,
                        new TNamedObject($get_class_name),
                    ),
                ]),
            );
        }
    }

    /**
     * A class_alias() declares its alias, a class_exists(), interface_exists() or enum_exists() of a missing class
     * makes it a phantom class
     *
     * @param 'class_alias'|'class_exists'|'interface_exists'|'enum_exists' $function_id
     */
    private static function handleClassLikeDeclarationCheck(
        Codebase $codebase,
        PhpParser\Node\Expr\FuncCall $stmt,
        string $function_id,
        Context $context,
    ): void {
        $class_name = self::getClassNameOfArgument($stmt->getArgs()[0] ?? null);

        if ($class_name === null) {
            return;
        }

        if ($function_id === 'class_alias') {
            // the alias is declared from here on, whatever was checked about it before, unless the class it aliases
            // may not exist: then class_alias() fails
            $alias_arg = $stmt->getArgs()[1] ?? null;

            if (($codebase->classlikes->classOrInterfaceOrEnumExists($class_name, null, $context)
                    || $codebase->classlikes->traitExists($class_name, null, $context))
                && $alias_arg
                && $alias_arg->value instanceof PhpParser\Node\Scalar\String_
            ) {
                $context->phantom_classes[strtolower(ltrim($alias_arg->value->value, '\\'))] = true;
            }

            return;
        }

        $exists = match ($function_id) {
            'class_exists' => $codebase->classlikes->classExists($class_name, null, $context),
            'interface_exists' => $codebase->classlikes->interfaceExists($class_name, null, $context),
            'enum_exists' => $codebase->classlikes->enumExists($class_name, null, $context),
        };

        if (!$exists) {
            $context->addPhantomClass(strtolower($class_name));
        }
    }

    /**
     * The class an argument names: a string literal, or `Foo::class`
     */
    private static function getClassNameOfArgument(?PhpParser\Node\Arg $arg): ?string
    {
        if ($arg === null) {
            return null;
        }

        if ($arg->value instanceof PhpParser\Node\Scalar\String_) {
            return ltrim($arg->value->value, '\\');
        }

        if ($arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
            && $arg->value->class instanceof PhpParser\Node\Name
            && $arg->value->name instanceof PhpParser\Node\Identifier
            && $arg->value->name->name === 'class'
        ) {
            return (string) $arg->value->class->getAttribute('resolvedName');
        }

        return null;
    }

    /**
     * Reports the arguments of builtin functions that PHP deprecates, whatever PHP version is analysed
     *
     * @param lowercase-string $function_id
     */
    private static function checkDeprecatedArguments(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        string $function_id,
    ): void {
        $types = [];
        $names = [];

        foreach ($stmt->getArgs() as $arg) {
            if ($arg->unpack) {
                return;
            }

            if ($arg->name !== null) {
                $names[$arg->name->toLowerString()] = true;
            } elseif ($names === []) {
                $types[] = $statements_analyzer->node_data->getType($arg->value);
            }
        }

        $message = null;

        if (isset(self::CSV_ESCAPE_POSITIONS[$function_id])
            && !isset($names['escape'])
            && count($types) <= self::CSV_ESCAPE_POSITIONS[$function_id]
        ) {
            $message = PhpDeprecations::getDeprecatedSinceMessage(
                'Relying on the default escape argument of ' . $function_id . '()',
                8_04_00,
                null,
            ) . ', pass it explicitly';
        }

        // the checks below need the position of every argument
        $positional_function_id = $names === [] ? $function_id : '';

        if (isset(self::PGSQL_LAST_CONNECTION_ARGUMENTS[$positional_function_id])
            && count($types) === self::PGSQL_LAST_CONNECTION_ARGUMENTS[$positional_function_id]
        ) {
            $message = PhpDeprecations::getDeprecatedSinceMessage(
                'Calling ' . $function_id . '() without a connection',
                8_01_00,
                null,
            ) . ', pass the connection as the first argument';
        }

        if (isset(self::MB_ENCODING_POSITIONS[$positional_function_id])) {
            self::checkDeprecatedEncodings($statements_analyzer, $stmt, $positional_function_id, $types);
        }

        $message ??= self::getDeprecatedCallShapeMessage($positional_function_id, $types)
            ?? self::getDeprecatedArgumentMessage($statements_analyzer, $stmt, $positional_function_id, $types);

        if ($message !== null) {
            IssueBuffer::maybeAdd(
                new DeprecatedFunction($message, new CodeLocation($statements_analyzer, $stmt), $function_id),
                $statements_analyzer->getSuppressedIssues(),
            );
        }
    }

    /**
     * Why calling the builtin $function_id with these arguments is deprecated, given how many there are
     *
     * @param list<?Union> $types the types of the arguments
     * @psalm-pure
     */
    private static function getDeprecatedCallShapeMessage(
        string $function_id,
        array $types,
    ): ?string {
        if (isset(self::DEPRECATED_CALL_SHAPES[$function_id])) {
            [$operator, $count, $subject, $since, $replacement, $suffix] = self::DEPRECATED_CALL_SHAPES[$function_id];

            if ($operator === '>' ? count($types) > $count : count($types) === $count) {
                return PhpDeprecations::getDeprecatedSinceMessage($subject, $since, $replacement) . $suffix;
            }

            return null;
        }

        switch ($function_id) {
            case 'dba_fetch':
                if (count($types) === 3 && ($types[1] ?? null)?->isInt()) {
                    return PhpDeprecations::getDeprecatedSinceMessage(
                        'Passing the connection as the third argument of dba_fetch()',
                        8_03_00,
                        null,
                    ) . ', pass it second and skip third';
                }

                return null;

            case 'readdir':
            case 'rewinddir':
            case 'closedir':
                if ($types === [] || $types[0]?->isNull() === true) {
                    return PhpDeprecations::getDeprecatedSinceMessage(
                        'Calling ' . $function_id . '() without a directory handle',
                        8_05_00,
                        null,
                    ) . ', pass the handle opendir() returned';
                }

                return null;
        }

        return null;
    }

    /**
     * Why passing these arguments to the builtin $function_id is deprecated, given their values
     *
     * @param list<?Union> $types the types of the arguments
     */
    private static function getDeprecatedArgumentMessage(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        string $function_id,
        array $types,
    ): ?string {
        switch ($function_id) {
            case 'ini_set':
            case 'ini_alter':
                if (isset($types[0]) && $types[0]->isSingleStringLiteral()) {
                    return PhpDeprecations::getIniSettingMessage($types[0]->getSingleStringLiteral()->value);
                }

                return null;

            case 'mb_strimwidth':
                if (self::isNegativeInt($types[2] ?? null)) {
                    return PhpDeprecations::getDeprecatedSinceMessage(
                        'A negative width argument of mb_strimwidth()',
                        8_03_00,
                        null,
                    );
                }

                return null;

            case 'trigger_error':
            case 'user_error':
                if (self::isIntLiteral($types[1] ?? null, E_USER_ERROR)) {
                    return PhpDeprecations::getDeprecatedSinceMessage(
                        'Calling ' . $function_id . '() with E_USER_ERROR',
                        8_04_00,
                        null,
                    ) . ', throw an exception or call exit()';
                }

                return null;

            case 'dba_key_split':
                if (($types[0] ?? null)?->isNull() || ($types[0] ?? null)?->isFalse()) {
                    return PhpDeprecations::getDeprecatedSinceMessage(
                        'Passing null or false to dba_key_split()',
                        8_04_00,
                        null,
                    );
                }

                return null;

            case 'chr':
                if (self::isIntOutside($types[0] ?? null, 0, 255)) {
                    return PhpDeprecations::getDeprecatedSinceMessage(
                        'Passing an integer outside of 0..255 to chr()',
                        8_05_00,
                        null,
                    );
                }

                return null;

            case 'ord':
                if (isset($types[0])
                    && $types[0]->isSingleStringLiteral()
                    && strlen($types[0]->getSingleStringLiteral()->value) !== 1
                ) {
                    return PhpDeprecations::getDeprecatedSinceMessage(
                        'Passing a string that is not one byte to ord()',
                        8_05_00,
                        null,
                    );
                }

                return null;

            case 'pow':
                if (isset($types[0], $types[1])) {
                    ArithmeticOpAnalyzer::checkZeroToNegativePower($statements_analyzer, $types[0], $types[1], $stmt);
                }

                return null;

            case 'spl_autoload_unregister':
                if (isset($types[0])
                    && $types[0]->isSingleStringLiteral()
                    && strtolower($types[0]->getSingleStringLiteral()->value) === 'spl_autoload_call'
                ) {
                    return PhpDeprecations::getDeprecatedSinceMessage(
                        'Unregistering spl_autoload_call() to unregister every autoloader',
                        8_05_00,
                        null,
                    ) . ', unregister each of spl_autoload_functions()';
                }

                return null;

            case 'http_build_query':
                if (self::canBeObject($types[0] ?? null)) {
                    self::reportDeprecatedObjectArgument(
                        $statements_analyzer,
                        $stmt,
                        $function_id,
                        'the data argument of http_build_query()',
                    );
                }

                return null;

            case 'deflate_init':
            case 'inflate_init':
                if (self::canBeObject($types[1] ?? null)) {
                    self::reportDeprecatedObjectArgument(
                        $statements_analyzer,
                        $stmt,
                        $function_id,
                        'the options argument of ' . $function_id . '()',
                    );
                }

                return null;

            case 'mb_convert_variables':
                for ($position = 2, $count = count($types); $position < $count; $position++) {
                    if (self::canBeObject($types[$position])) {
                        self::reportDeprecatedObjectArgument(
                            $statements_analyzer,
                            $stmt,
                            $function_id,
                            'the vars argument of mb_convert_variables()',
                        );

                        return null;
                    }
                }

                return null;

            case 'stream_filter_append':
            case 'stream_filter_prepend':
                if (self::canBeObject($types[3] ?? null)
                    && self::isAnyOf($types[1] ?? null, self::ARRAY_PARAMETER_FILTERS)
                ) {
                    self::reportDeprecatedObjectArgument(
                        $statements_analyzer,
                        $stmt,
                        $function_id,
                        'the parameters of the zlib and bzip2 stream filters',
                    );
                }

                return null;

            case 'is_a':
            case 'is_subclass_of':
                $allow_string = $types[2] ?? null;
                $allows_strings = $allow_string === null
                    ? $function_id === 'is_subclass_of'
                    : !$allow_string->isFalse();

                if (!$allows_strings && self::canBeString($types[0] ?? null)) {
                    return PhpDeprecations::getDeprecatedSinceMessage(
                        'Calling ' . $function_id . '() with a class name when allow_string is false',
                        8_06_00,
                        null,
                    ) . ', check class_exists() first and pass true for allow_string';
                }

                return null;

            case 'session_set_save_handler':
                if (count($types) > 2) {
                    return PhpDeprecations::getDeprecatedSinceMessage(
                        'Passing the session handler callables to session_set_save_handler()',
                        8_04_00,
                        null,
                    ) . ', pass an object implementing SessionHandlerInterface';
                } elseif (self::lacksSessionIdMethods($statements_analyzer, $types[0] ?? null)) {
                    return PhpDeprecations::getDeprecatedSinceMessage(
                        'A session handler without the create_sid() and validateId() methods',
                        8_06_00,
                        null,
                    ) . ', implement SessionIdInterface and SessionUpdateTimestampHandlerInterface';
                }

                return null;

            case 'fopen':
            case 'file_get_contents':
            case 'file_put_contents':
            case 'file':
            case 'readfile':
            case 'copy':
                // the path, or both paths of copy(), can be a php://filter URL
                if (self::chainsTooManyFilters($types[0] ?? null)
                    || ($function_id === 'copy' && self::chainsTooManyFilters($types[1] ?? null))
                ) {
                    return PhpDeprecations::getDeprecatedSinceMessage(
                        'A php://filter URL chaining more than ' . self::MAX_FILTERS . ' filters',
                        8_06_00,
                        null,
                    ) . ', append them with stream_filter_append() or raise the filter.max_filter_count context option';
                }

                return null;
        }

        return null;
    }

    /**
     * Reports the encodings mbstring deprecates, given to its function $function_id
     *
     * @param list<?Union> $types the types of the arguments
     */
    private static function checkDeprecatedEncodings(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        string $function_id,
        array $types,
    ): void {
        foreach (self::MB_ENCODING_POSITIONS[$function_id] ?? [] as $position) {
            if (!isset($types[$position])) {
                continue;
            }

            foreach ($types[$position]->getLiteralStrings() as $literal) {
                // from_encoding and encodings can list several, separated by commas
                foreach (explode(',', $literal->value) as $encoding) {
                    $message = PhpDeprecations::getMbEncodingMessage($encoding);

                    if ($message !== null) {
                        IssueBuffer::maybeAdd(
                            new DeprecatedFunction(
                                $message,
                                new CodeLocation($statements_analyzer, $stmt),
                                $function_id,
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }
                }
            }
        }
    }

    private static function reportDeprecatedObjectArgument(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        string $function_id,
        string $argument,
    ): void {
        IssueBuffer::maybeAdd(
            new DeprecatedFunction(
                PhpDeprecations::getDeprecatedSinceMessage(
                    'Passing an object as ' . $argument,
                    8_06_00,
                    'get_object_vars()',
                ),
                new CodeLocation($statements_analyzer, $stmt),
                $function_id,
            ),
            $statements_analyzer->getSuppressedIssues(),
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
    private static function isNegativeInt(?Union $type): bool
    {
        return $type !== null && $type->isSingleIntLiteral() && $type->getSingleIntLiteral()->value < 0;
    }

    /**
     * @psalm-pure
     */
    private static function isIntLiteral(?Union $type, int $value): bool
    {
        return $type !== null && $type->isSingleIntLiteral() && $type->getSingleIntLiteral()->value === $value;
    }

    /**
     * @psalm-pure
     */
    private static function isIntOutside(?Union $type, int $min, int $max): bool
    {
        if ($type === null || !$type->isSingleIntLiteral()) {
            return false;
        }

        $value = $type->getSingleIntLiteral()->value;

        return $value < $min || $value > $max;
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
