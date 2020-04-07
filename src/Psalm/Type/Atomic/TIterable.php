<?php
namespace Psalm\Type\Atomic;

use function count;
use function implode;
use Psalm\CodeLocation;
use Psalm\Internal\Type\TemplateResult;
use Psalm\StatementsSource;
use Psalm\Type\Atomic;
use function substr;
use function array_merge;

class TIterable extends Atomic
{
    use HasIntersectionTrait;
    use GenericTrait;

    /**
     * @var string
     */
    public $value = 'iterable';

    /**
     * @var bool
     */
    public $has_docblock_params = false;

    /**
     * @param list<\Psalm\Type\Union>     $type_params
     */
    public function __construct(array $type_params = [])
    {
        if ($type_params) {
            $this->has_docblock_params = true;
            $this->type_params = $type_params;
        } else {
            $this->type_params = [\Psalm\Type::getMixed(), \Psalm\Type::getMixed()];
        }
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true)
    {
        if ($include_extra && $this->extra_types) {
            // do nothing
        }

        return 'iterable';
    }

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return 'iterable';
    }

    public function getId(bool $nested = false)
    {
        $s = '';
        foreach ($this->type_params as $type_param) {
            $s .= $type_param->getId() . ', ';
        }

        $extra_types = '';

        if ($this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        return $this->value . '<' . substr($s, 0, -2) . '>' . $extra_types;
    }

    public function __toString()
    {
        return $this->getId();
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return string|null
     */
    public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    ) {
        return $php_major_version > 7
            || ($php_major_version === 7 && $php_minor_version >= 1)
            ? 'iterable'
            : null;
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return $this->type_params[0]->isMixed() && $this->type_params[1]->isMixed();
    }

    /**
     * @return bool
     */
    public function equals(Atomic $other_type)
    {
        if (!$other_type instanceof self) {
            return false;
        }

        if (count($this->type_params) !== count($other_type->type_params)) {
            return false;
        }

        foreach ($this->type_params as $i => $type_param) {
            if (!$type_param->equals($other_type->type_params[$i])) {
                return false;
            }
        }

        return true;
    }

    public function getChildNodes() : array
    {
        return array_merge($this->type_params, $this->extra_types !== null ? $this->extra_types : []);
    }
}
