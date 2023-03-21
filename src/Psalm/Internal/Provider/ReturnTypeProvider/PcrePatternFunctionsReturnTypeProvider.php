<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\MutableUnion;
use Psalm\Type\Union;
use Throwable;

use function array_map;
use function count;
use function preg_match;

/**
 * @internal
 */
class PcrePatternFunctionsReturnTypeProvider implements FunctionReturnTypeProviderInterface
{

    public static function getFunctionIds(): array
    {
        return ['preg_grep', 'preg_match', 'preg_match_all'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $args = $event->getCallArgs();
        $function_id = $event->getFunctionId();
        if ($function_id === 'preg_grep') {
            if (count($args) >= 2) {
                $pattern_type = $event->getStatementsSource()->getNodeTypeProvider()->getType($args[0]->value);
                $array_type = $event->getStatementsSource()->getNodeTypeProvider()->getType($args[1]->value);
                return static::getPregGrepReturnType($pattern_type, $array_type);
            }
        } elseif ($function_id === 'preg_match') {
            if (count($args) >= 1) {
                $pattern_type = $event->getStatementsSource()->getNodeTypeProvider()->getType($args[0]->value);
                return static::getPregMatchReturnType($pattern_type);
            }
        } elseif ($function_id === 'preg_match_all') {
            if (count($args) >= 1) {
                $pattern_type = $event->getStatementsSource()->getNodeTypeProvider()->getType($args[0]->value);
                return static::getPregMatchAllReturnType($pattern_type);
            }
        }
        return null;
    }

    private static function getPregMatchReturnType(?Union $pattern_type): Union
    {
        return static::computeReturnTypeFromPattern(
            $pattern_type,
            new MutableUnion([
                Type::getInt(false, 0)->getSingleAtomic(),
                Type::getInt(false, 1)->getSingleAtomic(),
            ]),
            Type::getFalse()->getSingleAtomic(),
        );
    }

    private static function getPregMatchAllReturnType(?Union $pattern_type): Union
    {
        return static::computeReturnTypeFromPattern(
            $pattern_type,
            new MutableUnion([
                new Atomic\TIntRange(0, null),
            ]),
            Type::getFalse()->getSingleAtomic(),
        );
    }

    private static function getPregGrepReturnType(?Union $pattern_type, ?Union $array_arg_type): ?Union
    {
        $return_type_builder = null;
        $array_arg_atomic = $array_arg_type
            && $array_arg_type->hasType('array')
            && ($array_atomic_type = $array_arg_type->getArray())
            && ($array_atomic_type instanceof TArray
                || $array_atomic_type instanceof TKeyedArray)
                ? $array_atomic_type
                : null;

        if ($array_arg_atomic instanceof TArray) {
            if ($array_arg_atomic instanceof Type\Atomic\TNonEmptyArray) {
                $return_type_builder = new Type\MutableUnion([new TArray($array_arg_atomic->type_params)]);
            } else {
                $return_type_builder = new Type\MutableUnion([$array_arg_atomic]);
            }
        } elseif ($array_arg_atomic instanceof TKeyedArray) {
            $properties = array_map(
                static fn(Union $type): Union => $type->setPossiblyUndefined(true),
                $array_arg_atomic->properties,
            );
            if ($array_arg_atomic->is_list) {
                $return_type_builder = new MutableUnion([
                    $array_arg_atomic->setProperties($properties)->getGenericArrayType(),
                ]);
            } else {
                $return_type_builder = new MutableUnion([$array_arg_atomic->setProperties($properties)]);
            }
        }

        if ($return_type_builder === null) {
            return null;
        }

        return static::computeReturnTypeFromPattern(
            $pattern_type,
            $return_type_builder,
            Type::getFalse()->getSingleAtomic(),
        );
    }

    private static function computeReturnTypeFromPattern(
        ?Union $pattern_type,
        MutableUnion $return_type_candidate,
        Atomic $invalid_pattern_return_type,
        Atomic $possibly_invalid_pattern_return_type = null
    ): Union {
        if (!$possibly_invalid_pattern_return_type) {
            $possibly_invalid_pattern_return_type = $invalid_pattern_return_type;
        }
        if ($pattern_type
            && !$pattern_type->isUnionEmpty()
            && $pattern_type->allStringLiterals()
        ) {
            ['all_valid' => $all_valid, 'any_valid' => $any_valid]
                = static::checkRegexPatterns($pattern_type->getLiteralStrings());
            if ($any_valid) {
                if (!$all_valid) {
                    static::addFailingReturnType($return_type_candidate, $possibly_invalid_pattern_return_type);
                }
            } else {
                $return_type_candidate = new MutableUnion([$invalid_pattern_return_type]);
            }
        } else {
            static::addFailingReturnType($return_type_candidate, $possibly_invalid_pattern_return_type);
        }
        return $return_type_candidate->freeze();
    }

    private static function addFailingReturnType(MutableUnion $type_builder, Atomic $failing_type): void
    {
        $type_builder->addType($failing_type);
        if ($failing_type instanceof Atomic\TNull) {
            $type_builder->ignore_nullable_issues = true;
        } elseif ($failing_type instanceof Atomic\TFalse) {
            $type_builder->ignore_falsable_issues = true;
        }
        //return $type_builder;
    }

    /**
     * @param Type\Atomic\TLiteralString[] $patterns
     * @return array{'all_valid': bool, 'any_valid': bool}
     */
    private static function checkRegexPatterns(array $patterns): array
    {
        $all_valid = true;
        $any_valid = false;
        foreach ($patterns as $pattern) {
            $current_valid = static::isValidRegexPattern($pattern->value);
            $all_valid = $all_valid && $current_valid;
            $any_valid = $any_valid || $current_valid;
        }
        return ['all_valid' => $all_valid, 'any_valid' => $any_valid];
    }

    private static function isValidRegexPattern(string $pattern): bool
    {
        try {
            return @preg_match($pattern, '') !== false;
        } catch (Throwable $_) {
            return false;
        }
    }
}
