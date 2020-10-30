<?php
namespace Psalm\Type\Atomic;

class TIntMaskOf extends TInt
{
    /** @var TScalarClassConstant|TKeyOfClassConstant|TValueOfClassConstant */
    public $value;

    /**
     * @param TScalarClassConstant|TKeyOfClassConstant|TValueOfClassConstant $value
     */
    public function __construct(\Psalm\Type\Atomic $value)
    {
        $this->value = $value;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'int-mask-of<' . $this->value->getKey() . '>';
    }

    public function getId(bool $nested = false): string
    {
        return $this->getKey();
    }

    /**
     * @param  array<string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): ?string {
        return $php_major_version >= 7 ? 'int' : null;
    }

    /**
     * @param  array<string, string> $aliased_classes
     *
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        if ($use_phpdoc_format) {
            return 'int';
        }

        return 'int-mask-of<'
            . $this->value->toNamespacedString($namespace, $aliased_classes, $this_class, false)
            . '>';
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }
}
