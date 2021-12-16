<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult2;
use Psalm\Type\Atomic;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

use function count;
use function get_class;

/**
 * Denotes a simple array of the form `array<TKey, TValue>`. It expects an array with two elements, both union types.
 */
class TArray extends Atomic
{
    use GenericTrait;

    /**
     * @var array{Union, Union}
     */
    public $type_params;

    /**
     * @var string
     */
    public $value = 'array';

    /**
     * Constructs a new instance of a generic type
     *
     * @param array{Union, Union} $type_params
     */
    public function __construct(array $type_params)
    {
        $this->type_params = $type_params;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'array';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): string {
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return $this->type_params[0]->isArrayKey() && $this->type_params[1]->isMixed();
    }

    public function equals(TypeNode $other_type, bool $ensure_source_equality): bool
    {
        if (get_class($other_type) !== static::class) {
            return false;
        }

        if ($this instanceof TNonEmptyArray
            && $other_type instanceof TNonEmptyArray
            && $this->count !== $other_type->count
        ) {
            return false;
        }

        if (count($this->type_params) !== count($other_type->type_params)) {
            return false;
        }

        foreach ($this->type_params as $i => $type_param) {
            if (!$type_param->equals($other_type->type_params[$i], $ensure_source_equality)) {
                return false;
            }
        }

        return true;
    }

    public function getAssertionString(bool $exact = false): string
    {
        if (!$exact || $this->type_params[1]->isMixed()) {
            return 'array';
        }

        return $this->toNamespacedString(null, [], null, false);
    }

    /**
     * @psalm-mutation-free
     */
    protected function containedByAtomic(
        Atomic $other,
        ?Codebase $codebase
        // bool $allow_interface_equality = false,
    ): TypeComparisonResult2 {
        switch (get_class($other)) {
            case TList::class:
                if ($this->type_params[1]->isEmpty()) {
                    return TypeComparisonResult2::true();
                }
                return (TypeComparisonResult2::notTrue())->and(
                    $this->type_params[1]->containedBy($other->type_param, $codebase)
                );
            case TKeyedArray::class:
                if ($this->type_params[0]->containedBy(new TEmpty())->result) {
                    return TypeComparisonResult2::true();
                }

                $all_keys_optional = true;
                foreach ($other->properties as $prop_type) {
                    $all_keys_optional = $all_keys_optional && $prop_type->possibly_undefined;
                    if (!$all_keys_optional) {
                        break;
                    }
                }

                if ($all_keys_optional) {
                    $result = TypeComparisonResult2::true();
                } else {
                    $result = TypeComparisonResult2::scalarCoerced();
                }
                return ($result)->and(
                    $this->type_params[0]->containedBy($other->getGenericKeyType(), $codebase)
                )->and(
                    $this->type_params[1]->containedBy($other->getGenericValueType(), $codebase)
                );
            case self::class:
            case TIterable::class:
                return $this->type_params[0]->containedBy($other->type_params[0], $codebase)->and(
                    $this->type_params[1]->containedBy($other->type_params[1], $codebase)
                );
        }

        return parent::containedByAtomic($other, $codebase);
    }
}
