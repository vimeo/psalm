<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

use function array_fill_keys;

use const PHP_URL_FRAGMENT;
use const PHP_URL_HOST;
use const PHP_URL_PASS;
use const PHP_URL_PATH;
use const PHP_URL_PORT;
use const PHP_URL_QUERY;
use const PHP_URL_SCHEME;
use const PHP_URL_USER;

/**
 * @internal
 */
final class ParseUrlReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['parse_url'];
    }

    private static ?Union $acceptable_int_component_type = null;
    private static ?Union $acceptable_string_component_type = null;
    private static ?Union $nullable_falsable_int = null;
    private static ?Union $nullable_falsable_string = null;
    private static ?Union $nullable_string_or_int = null;
    private static ?Union $return_type = null;

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        if (isset($call_args[1])) {
            $is_default_component = false;
            if ($component_type = $statements_source->node_data->getType($call_args[1]->value)) {
                if (!$component_type->hasMixed()) {
                    $codebase = $statements_source->getCodebase();

                    self::$acceptable_string_component_type ??= new Union([
                        new TLiteralInt(PHP_URL_SCHEME),
                        new TLiteralInt(PHP_URL_USER),
                        new TLiteralInt(PHP_URL_PASS),
                        new TLiteralInt(PHP_URL_HOST),
                        new TLiteralInt(PHP_URL_PATH),
                        new TLiteralInt(PHP_URL_QUERY),
                        new TLiteralInt(PHP_URL_FRAGMENT),
                    ]);

                    self::$acceptable_int_component_type ??= new Union([
                        new TLiteralInt(PHP_URL_PORT),
                    ]);

                    if (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $component_type,
                        self::$acceptable_string_component_type,
                    )) {
                        self::$nullable_falsable_string ??= new Union([
                            new TString,
                            new TFalse,
                            new TNull,
                        ], [
                            'ignore_nullable_issues' => $statements_source->getCodebase()
                                ->config->ignore_internal_nullable_issues,
                            'ignore_falsable_issues' => $statements_source->getCodebase()
                                ->config->ignore_internal_falsable_issues,
                        ]);
                        return self::$nullable_falsable_string;
                    }

                    if (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $component_type,
                        self::$acceptable_int_component_type,
                    )) {
                        self::$nullable_falsable_int ??= new Union([
                            new TInt,
                            new TFalse,
                            new TNull,
                        ], [
                            'ignore_nullable_issues' => $statements_source->getCodebase()
                                ->config->ignore_internal_nullable_issues,
                            'ignore_falsable_issues' => $statements_source->getCodebase()
                                ->config->ignore_internal_falsable_issues,
                        ]);
                        return self::$nullable_falsable_int;
                    }

                    if ($component_type->isSingleIntLiteral()) {
                        $component_type_type = $component_type->getSingleIntLiteral();
                        $is_default_component = $component_type_type->value <= -1;
                    }
                }
            }

            if (!$is_default_component) {
                self::$nullable_string_or_int ??= new Union([
                    new TString,
                    new TInt,
                    new TNull,
                ], [
                    'ignore_nullable_issues' => $statements_source->getCodebase()
                        ->config->ignore_internal_nullable_issues,
                ]);
                return self::$nullable_string_or_int;
            }
        }

        if (!self::$return_type) {
            $component_types = array_fill_keys(
                [
                    'scheme',
                    'user',
                    'pass',
                    'host',
                    'path',
                    'query',
                    'fragment',
                ],
                new Union([new TString()], ['possibly_undefined' => true]),
            );
            $component_types['port'] = new Union([new TInt()], ['possibly_undefined' => true]);

            self::$return_type = new Union([
                new TKeyedArray(
                    $component_types,
                    null,
                ),
                new TFalse(),
            ], [
                'ignore_falsable_issues' => $statements_source->getCodebase()->config->ignore_internal_falsable_issues,
            ]);
        }

        return self::$return_type;
    }
}
