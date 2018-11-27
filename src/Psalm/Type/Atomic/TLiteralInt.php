<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

class TLiteralInt extends TInt
{
    /** @var int */
    public $value;

    /**
     * @param int $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'int(' . $this->value . ')';
    }

    /**
     * @return string
     */
    public function getId()
    {
        return 'int(' . $this->value . ')';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  bool          $use_phpdoc_format
     *
     * @return string
     */
    public function toNamespacedString($namespace, array $aliased_classes, $this_class, $use_phpdoc_format)
    {
        return 'int';
    }
}
