<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;
use Throwable;

use function array_map;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

/**
 * @internal
 */
final class TypeWidener
{
    public static function widenIfTemplateUnconstrained(
        TTemplateParam $template,
        Union $type,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer
    ): Union {
        return $template->as->isMixed() && self::shouldWidenTemplate($template, $codebase, $statements_analyzer)
            ? self::widenUnion($type)
            : $type;
    }

    public static function widenUnion(Union $type): Union
    {
        $without_literals = array_map(
            fn(Atomic $atomic) => self::widenAtomic($atomic),
            $type->getAtomicTypes(),
        );

        return $type->setTypes($without_literals);
    }

    public static function widenAtomic(Atomic $atomic): Atomic
    {
        if ($atomic instanceof TTrue || $atomic instanceof TFalse) {
            return new TBool($atomic->from_docblock);
        }

        if ($atomic instanceof TLiteralInt || $atomic instanceof TIntRange) {
            return new TInt($atomic->from_docblock);
        }

        if ($atomic instanceof TLiteralString) {
            return $atomic->value === ''
                ? new TString($atomic->from_docblock)
                : new TNonEmptyString($atomic->from_docblock);
        }

        if ($atomic instanceof TLiteralFloat) {
            return new TFloat($atomic->from_docblock);
        }

        if ($atomic instanceof TKeyedArray) {
            if ($atomic->is_list) {
                $value_type = self::widenUnion($atomic->getGenericValueType());

                return $atomic->isNonEmpty()
                    ? Type::getNonEmptyListAtomic($value_type, $atomic->from_docblock)
                    : Type::getListAtomic($value_type, $atomic->from_docblock);
            }

            $type_params = self::widenTypeParams([
                $atomic->getGenericKeyType(),
                $atomic->getGenericValueType(),
            ]);

            if ($atomic->isNonEmpty()) {
                return new TNonEmptyArray(
                    $type_params,
                    null,
                    null,
                    'non-empty-array',
                    $atomic->from_docblock,
                );
            }

            return new TArray($type_params, $atomic->from_docblock);
        }

        if ($atomic instanceof TGenericObject) {
            return new TGenericObject(
                $atomic->value,
                self::widenTypeParams($atomic->type_params),
                $atomic->remapped_params,
                $atomic->is_static,
                self::widenExtraTypes($atomic->extra_types),
                $atomic->from_docblock,
            );
        }

        if ($atomic instanceof TNamedObject) {
            return new TNamedObject(
                $atomic->value,
                $atomic->is_static,
                $atomic->definite_class,
                self::widenExtraTypes($atomic->extra_types),
                $atomic->from_docblock,
            );
        }

        if ($atomic instanceof TObjectWithProperties) {
            return new TObjectWithProperties(
                self::widenTypeParams($atomic->properties),
                $atomic->methods,
                self::widenExtraTypes($atomic->extra_types),
            );
        }

        if ($atomic instanceof TIterable) {
            return new TIterable(
                self::widenTypeParams($atomic->type_params),
                self::widenExtraTypes($atomic->extra_types),
                $atomic->from_docblock,
            );
        }

        if ($atomic instanceof TNonEmptyArray) {
            return new TNonEmptyArray(
                self::widenTypeParams($atomic->type_params),
                $atomic->count,
                $atomic->min_count,
                'non-empty-array',
                $atomic->from_docblock,
            );
        }

        if ($atomic instanceof TArray) {
            return new TArray(
                self::widenTypeParams($atomic->type_params),
                $atomic->from_docblock,
            );
        }

        return $atomic;
    }

    private static function shouldWidenTemplate(
        TTemplateParam $template,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer
    ): bool {
        $should_widen = static function (?string $type = null) use ($codebase): bool {
            switch ($type) {
                case 'widen':
                    return true;
                case 'narrow':
                    return false;
                default:
                    return $codebase->config->widen_unconstrained_templates;
            }
        };

        try {
            if (strpos($template->defining_class, 'fn-') === 0) {
                /** @var non-empty-string */
                $function_like_id = substr($template->defining_class, strlen('fn-'));

                if (MethodIdentifier::isValidMethodIdReference($function_like_id)) {
                    $method_id = MethodIdentifier::wrap($function_like_id);
                    $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                    return $should_widen(
                        $codebase->methods
                            ->getStorage($declaring_method_id ?? $method_id)
                            ->widen_unconstrained_templates,
                    );
                }

                return $should_widen(
                    $codebase->functions
                        ->getStorage($statements_analyzer, strtolower($function_like_id))
                        ->widen_unconstrained_templates,
                );
            }

            return $should_widen(
                $codebase->classlike_storage_provider
                    ->get($template->defining_class)
                    ->widen_unconstrained_templates,
            );
        } catch (Throwable $e) {
            return $should_widen();
        }
    }

    /**
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $extra_types
     * @return array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject>
     */
    private static function widenExtraTypes(array $extra_types): array
    {
        /** @var array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> */
        return array_map(
            fn(Atomic $intersection) => self::widenAtomic($intersection),
            $extra_types,
        );
    }

    /**
     * @template TTypeParams of Union[]
     * @param TTypeParams $type_params
     * @return TTypeParams
     */
    private static function widenTypeParams(array $type_params): array
    {
        /** @var TTypeParams */
        return array_map(
            fn(Union $type_param) => self::widenUnion($type_param),
            $type_params,
        );
    }
}
