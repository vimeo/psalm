<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

use function get_class;

/**
 * Denotes the `string` type, where the exact value is unknown.
 *
 * @psalm-immutable
 */
class TString extends Scalar
{
    public ?bool $lowercase = null;

    public function __construct(bool $from_docblock = false, ?bool $lowercase = null)
    {
        parent::__construct($from_docblock);
        $this->lowercase = $lowercase;
    }

    /**
     * @psalm-pure
     */
    public static function isPlain(Atomic $atomic): bool
    {
        return get_class($atomic) === self::class
            && $atomic->lowercase === null;
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id
    ): ?string {
        return $analysis_php_version_id >= 7_00_00 ? 'string' : null;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'string';
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        if ($this->lowercase !== null) {
            return $this->lowercase ? 'lowercase-string' : 'non-lowercase-string';
        }
        return parent::getId();
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        if ($this->lowercase !== null) {
            return false;
        }
        return parent::canBeFullyExpressedInPhp($analysis_php_version_id);
    }
}
