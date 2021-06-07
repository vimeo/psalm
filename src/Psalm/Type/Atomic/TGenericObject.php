<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

use function array_merge;
use function count;
use function implode;
use function substr;

/**
 * Denotes an object type that has generic parameters e.g. `ArrayObject<string, Foo\Bar>`
 */
class TGenericObject extends TNamedObject
{
    use GenericTrait;

    /**
     * @var non-empty-list<\Psalm\Type\Union>
     */
    public $type_params;

    /** @var bool if the parameters have been remapped to another class */
    public $remapped_params = false;

    /**
     * @param string                            $value the name of the object
     * @param non-empty-list<\Psalm\Type\Union>     $type_params
     */
    public function __construct(string $value, array $type_params)
    {
        if ($value[0] === '\\') {
            $value = substr($value, 1);
        }

        $this->value = $value;
        $this->type_params = $type_params;
    }

    public function getKey(bool $include_extra = true): string
    {
        $s = '';

        foreach ($this->type_params as $type_param) {
            $s .= $type_param->getKey() . ', ';
        }

        $extra_types = '';

        if ($include_extra && $this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        return $this->value . '<' . substr($s, 0, -2) . '>' . $extra_types;
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
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
    ): ?string {
        return parent::toNamespacedString($namespace, $aliased_classes, $this_class, false);
    }

    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        if (!$other_type instanceof self) {
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
        return $this->value;
    }

    public function getChildNodes() : array
    {
        return array_merge($this->type_params, $this->extra_types !== null ? $this->extra_types : []);
    }
}
