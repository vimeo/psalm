<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Interner;
use Psalm\StrId;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_values;
use function count;
use function preg_quote;
use function preg_replace;
use function str_contains;
use function stripos;

/**
 * Denotes the `class-string` type, used to describe a string representing a valid PHP class.
 * The parent type from which the classes descend may or may not be specified in the constructor.
 *
 * @psalm-immutable
 * @api
 */
class TClassString extends TString
{
    /**
     * @param int $as interned name of the class bound, StrId::object if none
     */
    public function __construct(
        public int $as = StrId::object,
        public ?TNamedObject $as_type = null,
        public bool $is_loaded = false,
        public bool $is_interface = false,
        public bool $is_enum = false,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);
    }
    /**
     * @return static
     */
    public function setAs(int $as, ?TNamedObject $as_type): self
    {
        if ($this->as === $as && $this->as_type === $as_type) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->as = $as;
        $cloned->as_type = $as_type;
        return $cloned;
    }
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        if ($this->is_interface) {
            $key = 'interface-string';
        } elseif ($this->is_enum) {
            $key = 'enum-string';
        } else {
            $key = 'class-string';
        }

        return $key . ($this->as === StrId::object ? '' : '<' . $this->as_type . '>');
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if ($this->is_interface) {
            $key = 'interface-string';
        } elseif ($this->is_enum) {
            $key = 'enum-string';
        } else {
            $key = 'class-string';
        }

        return ($this->is_loaded ? 'loaded-' : '') . $key
            . ($this->as === StrId::object ? '' : '<' . $this->as_type . '>');
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getAssertionString(): string
    {
        return 'class-string';
    }

    /**
     * @param array<int, int> $aliased_classes
     * @psalm-pure
     */
    #[Override]
    public function toPhpString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        int $analysis_php_version_id,
    ): ?string {
        return 'string';
    }

    /**
     * @param array<int, int> $aliased_classes
     */
    #[Override]
    public function toNamespacedString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        bool $use_phpdoc_format,
    ): string {
        if ($this->as === StrId::object) {
            return 'class-string';
        }

        $as = Interner::str($this->as);
        $namespace = $namespace === null ? '' : Interner::str($namespace);

        if ($namespace !== '' && stripos($as, $namespace . '\\') === 0) {
            return 'class-string<' . preg_replace(
                '/^' . preg_quote($namespace . '\\') . '/i',
                '',
                $as,
            ) . '>';
        }

        if ($namespace === '' && !str_contains($as, '\\')) {
            return 'class-string<' . $as . '>';
        }

        $as_lc = Interner::lower($this->as);
        if (isset($aliased_classes[$as_lc])) {
            return 'class-string<' . Interner::str($aliased_classes[$as_lc]) . '>';
        }

        return 'class-string<\\' . $as . '>';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    #[Override]
    protected function getChildNodeKeys(): array
    {
        return $this->as_type ? ['as_type'] : [];
    }

    /**
     * @return static
     */
    #[Override]
    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer = null,
        ?Atomic $input_type = null,
        ?int $input_arg_offset = null,
        ?int $calling_class = null,
        ?int $calling_function = null,
        bool $replace = true,
        bool $add_lower_bound = false,
        int $depth = 0,
    ): self {
        if (!$this->as_type) {
            return $this;
        }

        if ($input_type instanceof TLiteralClassString) {
            $input_object_type = new TNamedObject($input_type->class_name);
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
            $cloned->as = StrId::object;
        }
        return $cloned;
    }
}
