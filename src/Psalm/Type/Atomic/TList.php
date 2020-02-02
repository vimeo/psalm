<?php
namespace Psalm\Type\Atomic;

use function get_class;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\StatementsSource;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\UnionTemplateHandler;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * Represents an array where we know its key values
 */
class TList extends \Psalm\Type\Atomic
{
    /**
     * @var Union
     */
    public $type_param;

    const KEY = 'list';

    /**
     * Constructs a new instance of a list
     */
    public function __construct(Union $type_param)
    {
        $this->type_param = $type_param;
    }

    public function __toString()
    {
        /** @psalm-suppress MixedOperand */
        return static::KEY . '<' . $this->type_param . '>';
    }

    public function getId(bool $nested = false)
    {
        /** @psalm-suppress MixedOperand */
        return static::KEY . '<' . $this->type_param->getId() . '>';
    }

    public function __clone()
    {
        $this->type_param = clone $this->type_param;
    }

    /**
     * @param  array<string, string> $aliased_classes
     *
     * @return string
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ) {
        if ($use_phpdoc_format) {
            return (new TArray([Type::getInt(), $this->type_param]))
                ->toNamespacedString(
                    $namespace,
                    $aliased_classes,
                    $this_class,
                    $use_phpdoc_format
                );
        }

        /** @psalm-suppress MixedOperand */
        return static::KEY
            . '<'
            . $this->type_param->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                $use_phpdoc_format
            )
            . '>';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return string
     */
    public function toPhpString($namespace, array $aliased_classes, $this_class, $php_major_version, $php_minor_version)
    {
        return 'array';
    }

    public function canBeFullyExpressedInPhp()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'array';
    }

    public function setFromDocblock()
    {
        $this->from_docblock = true;
        $this->type_param->setFromDocblock();
    }

    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        Codebase $codebase = null,
        Atomic $input_type = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_upper_bound = false,
        int $depth = 0
    ) : Atomic {
        $list = clone $this;

        foreach ([Type::getInt(), $list->type_param] as $offset => $type_param) {
            $input_type_param = null;

            if (($input_type instanceof Atomic\TGenericObject
                    || $input_type instanceof Atomic\TIterable
                    || $input_type instanceof Atomic\TArray)
                &&
                    isset($input_type->type_params[$offset])
            ) {
                $input_type_param = clone $input_type->type_params[$offset];
            } elseif ($input_type instanceof Atomic\ObjectLike) {
                if ($offset === 0) {
                    $input_type_param = $input_type->getGenericKeyType();
                } else {
                    $input_type_param = $input_type->getGenericValueType();
                }
            } elseif ($input_type instanceof Atomic\TList) {
                if ($offset === 0) {
                    continue;
                }

                $input_type_param = clone $input_type->type_param;
            }

            $type_param = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                $type_param,
                $template_result,
                $codebase,
                $input_type_param,
                $calling_class,
                $calling_function,
                $replace,
                $add_upper_bound,
                $depth + 1
            );

            if ($offset === 1) {
                $list->type_param = $type_param;
            }
        }

        return $list;
    }

    /**
     * @param  array<string, array<string, array{Type\Union, 1?:int}>>  $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types, ?Codebase $codebase)
    {
        $this->type_param->replaceTemplateTypesWithArgTypes($template_types);
    }

    /**
     * @return list<Type\Atomic\TTemplateParam>
     */
    public function getTemplateTypes() : array
    {
        return $this->type_param->getTemplateTypes();
    }

    /**
     * @return bool
     */
    public function equals(Atomic $other_type)
    {
        if (get_class($other_type) !== static::class) {
            return false;
        }

        if (!$this->type_param->equals($other_type->type_param)) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return 'list';
    }

    /**
     * @param  StatementsSource $source
     * @param  CodeLocation     $code_location
     * @param  array<string>    $suppressed_issues
     * @param  array<string, bool> $phantom_classes
     * @param  bool             $inferred
     *
     * @return void
     */
    public function check(
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        array $phantom_classes = [],
        bool $inferred = true,
        bool $prevent_template_covariance = false
    ) {
        if ($this->checked) {
            return;
        }

        $this->type_param->check(
            $source,
            $code_location,
            $suppressed_issues,
            $phantom_classes,
            $inferred,
            $prevent_template_covariance
        );

        $this->checked = true;
    }
}
