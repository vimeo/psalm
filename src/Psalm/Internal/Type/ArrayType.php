<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function count;

/**
 * @internal
 */
final class ArrayType
{
    public function __construct(public Union $key, public Union $value, public bool $is_list, public ?int $count)
    {
    }

    /**
     * @return (
     *     $type is TKeyedArray ? self : (
     *         $type is TNonEmptyArray ? self : (
     *             $type is TArray ? self : null
     *         )
     *     )
     * )
     */
    public static function infer(Atomic $type): ?self
    {
        if ($type instanceof TKeyedArray) {
            $count = null;
            if ($type->isSealed()) {
                $count = count($type->properties);
            }

            return new self(
                $type->getGenericKeyType(),
                $type->getGenericValueType(),
                $type->is_list,
                $count,
            );
        }

        if ($type instanceof TNonEmptyArray) {
            return new self(
                $type->type_params[0],
                $type->type_params[1],
                false,
                $type->count,
            );
        }

        if ($type instanceof TArray) {
            $empty = $type->isEmptyArray();
            return new self(
                $type->type_params[0],
                $type->type_params[1],
                false,
                $empty?0:null,
            );
        }

        return null;
    }

    // Reconciliation widens unsupported keys; containment must intersect with array-key instead.
    public static function refineKeyType(Union $key_type): Union
    {
        return self::refineKeyTypeInner($key_type) ?? $key_type;
    }

    private static function refineKeyTypeInner(Union $key_type): ?Union
    {
        $refined = false;
        $types = [];
        foreach ($key_type->getAtomicTypes() as $cat) {
            if ($cat instanceof TTemplateParam) {
                $as = self::refineKeyTypeInner($cat->as);
                if ($as) {
                    $refined = true;
                    $types []= $cat->replaceAs($as);
                } else {
                    $types []= $cat;
                }
            } elseif ($cat instanceof TArrayKey || $cat instanceof TString || $cat instanceof TInt) {
                $types []= $cat;
            } else {
                $refined = true;
                $types []= new TArrayKey;
            }
        }

        if ($refined) {
            return $key_type->getBuilder()->setTypes($types)->freeze();
        }

        return null;
    }
}
