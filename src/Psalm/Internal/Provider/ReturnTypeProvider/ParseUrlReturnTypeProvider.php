<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;

class ParseUrlReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return ['parse_url'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function get(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        if (count($call_args) > 1) {
            if (isset($call_args[1]->value->inferredType)) {
                $component_type = $call_args[1]->value->inferredType;

                if (!$component_type->hasMixed()) {
                    $codebase = $statements_analyzer->getCodebase();

                    $acceptable_string_component_type = new Type\Union([
                        new Type\Atomic\TLiteralInt(PHP_URL_SCHEME),
                        new Type\Atomic\TLiteralInt(PHP_URL_USER),
                        new Type\Atomic\TLiteralInt(PHP_URL_PASS),
                        new Type\Atomic\TLiteralInt(PHP_URL_HOST),
                        new Type\Atomic\TLiteralInt(PHP_URL_PATH),
                        new Type\Atomic\TLiteralInt(PHP_URL_QUERY),
                        new Type\Atomic\TLiteralInt(PHP_URL_FRAGMENT),
                    ]);

                    $acceptable_int_component_type = new Type\Union([
                        new Type\Atomic\TLiteralInt(PHP_URL_PORT)
                    ]);

                    if (TypeAnalyzer::isContainedBy(
                        $codebase,
                        $component_type,
                        $acceptable_string_component_type
                    )) {
                        $nullable_string = new Type\Union([
                            new Type\Atomic\TString,
                            new Type\Atomic\TNull
                        ]);

                        $codebase = $statements_analyzer->getCodebase();

                        if ($codebase->config->ignore_internal_nullable_issues) {
                            $nullable_string->ignore_nullable_issues = true;
                        }

                        return $nullable_string;
                    }

                    if (TypeAnalyzer::isContainedBy(
                        $codebase,
                        $component_type,
                        $acceptable_int_component_type
                    )) {
                        $nullable_int = new Type\Union([
                            new Type\Atomic\TInt,
                            new Type\Atomic\TNull
                        ]);

                        $codebase = $statements_analyzer->getCodebase();

                        if ($codebase->config->ignore_internal_nullable_issues) {
                            $nullable_int->ignore_nullable_issues = true;
                        }

                        return $nullable_int;
                    }
                }
            }

            $nullable_string_or_int = new Type\Union([
                new Type\Atomic\TString,
                new Type\Atomic\TInt,
                new Type\Atomic\TNull
            ]);

            $codebase = $statements_analyzer->getCodebase();

            if ($codebase->config->ignore_internal_nullable_issues) {
                $nullable_string_or_int->ignore_nullable_issues = true;
            }

            return $nullable_string_or_int;
        }

        $component_key_type = new Type\Union([
            new Type\Atomic\TLiteralString('scheme'),
            new Type\Atomic\TLiteralString('user'),
            new Type\Atomic\TLiteralString('pass'),
            new Type\Atomic\TLiteralString('host'),
            new Type\Atomic\TLiteralString('port'),
            new Type\Atomic\TLiteralString('path'),
            new Type\Atomic\TLiteralString('query'),
            new Type\Atomic\TLiteralString('fragment'),
        ]);

        $nullable_string_or_int = new Type\Union([
            new Type\Atomic\TArray([$component_key_type, Type::getMixed()]),
            new Type\Atomic\TFalse
        ]);

        $codebase = $statements_analyzer->getCodebase();

        if ($codebase->config->ignore_internal_falsable_issues) {
            $nullable_string_or_int->ignore_falsable_issues = true;
        }

        return $nullable_string_or_int;
    }
}
