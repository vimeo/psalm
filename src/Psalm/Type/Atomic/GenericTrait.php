<?php
namespace Psalm\Type\Atomic;

use function array_map;
use function implode;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\IssueBuffer;
use Psalm\Issue\InvalidTemplateParam;
use Psalm\Issue\MissingTemplateParam;
use Psalm\Issue\TooManyTemplateParams;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Union;
use function substr;

trait GenericTrait
{
    /**
     * @var non-empty-list<Union>
     */
    public $type_params;

    public function __toString()
    {
        $s = '';
        foreach ($this->type_params as $type_param) {
            $s .= $type_param . ', ';
        }

        $extra_types = '';

        if ($this instanceof TNamedObject && $this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        return $this->value . '<' . substr($s, 0, -2) . '>' . $extra_types;
    }

    /**
     * @return string
     */
    public function getId()
    {
        $s = '';
        foreach ($this->type_params as $type_param) {
            $s .= $type_param->getId() . ', ';
        }

        $extra_types = '';

        if ($this instanceof TNamedObject && $this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        return $this->value . '<' . substr($s, 0, -2) . '>' . $extra_types;
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
        $base_value = $this instanceof TNamedObject
            ? parent::toNamespacedString($namespace, $aliased_classes, $this_class, $use_phpdoc_format)
            : $this->value;

        if ($base_value === 'non-empty-array') {
            $base_value = 'array';
        }

        if ($use_phpdoc_format) {
            if ($this instanceof TNamedObject || $this instanceof TIterable) {
                return $base_value;
            }

            $value_type = $this->type_params[1];

            if ($value_type->isMixed() || $value_type->isEmpty()) {
                return $base_value;
            }

            $value_type_string = $value_type->toNamespacedString($namespace, $aliased_classes, $this_class, true);

            if (!$value_type->isSingle()) {
                return '(' . $value_type_string . ')[]';
            }

            return $value_type_string . '[]';
        }

        $extra_types = '';

        if ($this instanceof TNamedObject && $this->extra_types) {
            $extra_types = '&' . implode(
                '&',
                array_map(
                    /**
                     * @return string
                     */
                    function (Atomic $extra_type) use ($namespace, $aliased_classes, $this_class) {
                        return $extra_type->toNamespacedString($namespace, $aliased_classes, $this_class, false);
                    },
                    $this->extra_types
                )
            );
        }

        return $base_value .
                '<' .
                implode(
                    ', ',
                    array_map(
                        /**
                         * @return string
                         */
                        function (Union $type_param) use ($namespace, $aliased_classes, $this_class) {
                            return $type_param->toNamespacedString($namespace, $aliased_classes, $this_class, false);
                        },
                        $this->type_params
                    )
                ) .
                '>' . $extra_types;
    }

    public function __clone()
    {
        foreach ($this->type_params as &$type_param) {
            $type_param = clone $type_param;
        }
    }

    /**
     * @return void
     */
    public function setFromDocblock()
    {
        $this->from_docblock = true;

        foreach ($this->type_params as $type_param) {
            $type_param->setFromDocblock();
        }
    }

    /**
     * @param  array<string, array<string, array{Type\Union}>>     $template_types
     * @param  array<string, array<string, array{Type\Union, 1?:int}>>     $generic_params
     * @param  Atomic|null              $input_type
     *
     * @return void
     */
    public function replaceTemplateTypesWithStandins(
        array &$template_types,
        array &$generic_params,
        Codebase $codebase = null,
        Atomic $input_type = null,
        bool $replace = true,
        bool $add_upper_bound = false,
        int $depth = 0
    ) {
        if ($input_type instanceof Atomic\TList) {
            $input_type = new Atomic\TArray([Type::getInt(), $input_type->type_param]);
        }

        foreach ($this->type_params as $offset => $type_param) {
            $input_type_param = null;

            if (($input_type instanceof Atomic\TGenericObject
                    || $input_type instanceof Atomic\TIterable
                    || $input_type instanceof Atomic\TArray)
                &&
                    isset($input_type->type_params[$offset])
            ) {
                $input_type_param = $input_type->type_params[$offset];
            } elseif ($input_type instanceof Atomic\ObjectLike) {
                if ($offset === 0) {
                    $input_type_param = $input_type->getGenericKeyType();
                } elseif ($offset === 1) {
                    $input_type_param = $input_type->getGenericValueType();
                } else {
                    throw new \UnexpectedValueException('Not expecting offset of ' . $offset);
                }
            }

            $type_param->replaceTemplateTypesWithStandins(
                $template_types,
                $generic_params,
                $codebase,
                $input_type_param,
                $replace,
                $add_upper_bound,
                $depth + 1
            );
        }
    }

    /**
     * @param  array<string, array<string, array{Type\Union, 1?:int}>>  $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types, ?Codebase $codebase)
    {
        foreach ($this->type_params as $type_param) {
            $type_param->replaceTemplateTypesWithArgTypes($template_types, $codebase);
        }

        if ($this instanceof TGenericObject || $this instanceof TIterable) {
            $this->replaceIntersectionTemplateTypesWithArgTypes($template_types, $codebase);
        }
    }

    /**
     * @param  StatementsSource $source
     * @param  CodeLocation     $code_location
     * @param  array<string>    $suppressed_issues
     * @param  array<string, bool> $phantom_classes
     * @param  bool             $inferred
     *
     * @return false|null
     */
    public function checkGenericParams(
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        array $phantom_classes = [],
        bool $inferred = true,
        bool $prevent_template_covariance = false
    ) {
        $codebase = $source->getCodebase();

        if ($this instanceof Type\Atomic\TGenericObject) {
            try {
                $class_storage = $codebase->classlike_storage_provider->get($this->value);
            } catch (\InvalidArgumentException $e) {
                return;
            }

            $expected_type_params = $class_storage->template_types ?: [];
        } else {
            $expected_type_params = [
                'TKey' => [
                    '' => [Type::getMixed(), null],
                ],
                'TValue' => [
                    '' => [Type::getMixed(), null],
                ],
            ];
        }

        $template_type_count = \count($expected_type_params);
        $template_param_count = \count($this->type_params);

        if ($template_type_count > $template_param_count) {
            if (IssueBuffer::accepts(
                new MissingTemplateParam(
                    $this->value . ' has missing template params, expecting '
                        . $template_type_count,
                    $code_location
                ),
                $suppressed_issues
            )) {
                // fall through
            }
        } elseif ($template_type_count < $template_param_count) {
            if (IssueBuffer::accepts(
                new TooManyTemplateParams(
                    $this->value . ' has too many template params, expecting '
                        . $template_type_count,
                    $code_location
                ),
                $suppressed_issues
            )) {
                // fall through
            }
        }

        foreach ($this->type_params as $i => $type_param) {
            if ($type_param->check(
                $source,
                $code_location,
                $suppressed_issues,
                $phantom_classes,
                $inferred,
                $prevent_template_covariance
            ) === false) {
                return false;
            }

            if (isset(\array_values($expected_type_params)[$i])) {
                $expected_type_param = \reset(\array_values($expected_type_params)[$i])[0];
                $template_name = \array_keys($expected_type_params)[$i];

                $type_param = ExpressionAnalyzer::fleshOutType(
                    $codebase,
                    $type_param,
                    $source->getFQCLN(),
                    $source->getFQCLN(),
                    $source->getParentFQCLN()
                );

                if (!TypeAnalyzer::isContainedBy($codebase, $type_param, $expected_type_param)) {
                    if (IssueBuffer::accepts(
                        new InvalidTemplateParam(
                            'Extended template param ' . $template_name . ' expects type '
                                . $expected_type_param->getId()
                                . ', type ' . $type_param->getId() . ' given',
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        // fall through
                    }
                }
            }
        }
    }
}
