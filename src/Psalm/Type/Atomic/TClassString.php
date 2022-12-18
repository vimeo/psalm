<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_values;
use function count;
use function preg_quote;
use function preg_replace;
use function stripos;
use function strpos;
use function strtolower;

/**
 * Denotes the `class-string` type, used to describe a string representing a valid PHP class.
 * The parent type from which the classes descend may or may not be specified in the constructor.
 *
 * @psalm-immutable
 */
class TClassString extends TString
{
    /**
     * @var string
     */
    public $as;

    public ?TNamedObject $as_type;

    /** @var bool */
    public $is_loaded = false;

    /** @var bool */
    public $is_interface = false;

    /** @var bool */
    public $is_enum = false;

    public function __construct(
        string $as = 'object',
        ?TNamedObject $as_type = null,
        bool $is_loaded = false,
        bool $is_interface = false,
        bool $is_enum = false,
        bool $from_docblock = false
    ) {
        $this->as = $as;
        $this->as_type = $as_type;
        $this->is_loaded = $is_loaded;
        $this->is_interface = $is_interface;
        $this->is_enum = $is_enum;
        $this->from_docblock = $from_docblock;
    }
    /**
     * @return static
     */
    public function setAs(string $as, ?TNamedObject $as_type): self
    {
        if ($this->as === $as && $this->as_type === $as_type) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->as = $as;
        $cloned->as_type = $as_type;
        return $cloned;
    }
    public function getKey(bool $include_extra = true): string
    {
        if ($this->is_interface) {
            $key = 'interface-string';
        } elseif ($this->is_enum) {
            $key = 'enum-string';
        } else {
            $key = 'class-string';
        }

        return $key . ($this->as === 'object' ? '' : '<' . $this->as_type . '>');
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        if ($this->is_interface) {
            $key = 'interface-string';
        } elseif ($this->is_enum) {
            $key = 'enum-string';
        } else {
            $key = 'class-string';
        }

        return ($this->is_loaded ? 'loaded-' : '') . $key . ($this->as === 'object' ? '' : '<' . $this->as_type . '>');
    }

    public function getAssertionString(): string
    {
        return 'class-string';
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
        return 'string';
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        if ($this->as === 'object') {
            return 'class-string';
        }

        if ($namespace && stripos($this->as, $namespace . '\\') === 0) {
            return 'class-string<' . preg_replace(
                '/^' . preg_quote($namespace . '\\') . '/i',
                '',
                $this->as,
            ) . '>';
        }

        if (!$namespace && strpos($this->as, '\\') === false) {
            return 'class-string<' . $this->as . '>';
        }

        if (isset($aliased_classes[strtolower($this->as)])) {
            return 'class-string<' . $aliased_classes[strtolower($this->as)] . '>';
        }

        return 'class-string<\\' . $this->as . '>';
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    protected function getChildNodeKeys(): array
    {
        return $this->as_type ? ['as_type'] : [];
    }

    /**
     * @return static
     */
    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer = null,
        ?Atomic $input_type = null,
        ?int $input_arg_offset = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_lower_bound = false,
        int $depth = 0
    ): self {
        if (!$this->as_type) {
            return $this;
        }

        if ($input_type instanceof TLiteralClassString) {
            $input_object_type = new TNamedObject($input_type->value);
        } elseif ($input_type instanceof TClassString && $input_type->as_type) {
            $input_object_type = $input_type->as_type;
        } else {
            $input_object_type = new TObject();
        }

        $as_type = TemplateStandinTypeReplacer::replace(
            new Union([$this->as_type]),
            $template_result,
            $codebase,
            $statements_analyzer,
            new Union([$input_object_type]),
            $input_arg_offset,
            $calling_class,
            $calling_function,
            $replace,
            $add_lower_bound,
            null,
            $depth,
        );

        $as_type_types = array_values($as_type->getAtomicTypes());

        $as_type = count($as_type_types) === 1
            && $as_type_types[0] instanceof TNamedObject
            ? $as_type_types[0]
            : null;

        if ($this->as_type === $as_type) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->as_type = $as_type;
        if (!$cloned->as_type) {
            $cloned->as = 'object';
        }
        return $cloned;
    }
}
