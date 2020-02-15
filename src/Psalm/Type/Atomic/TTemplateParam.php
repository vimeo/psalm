<?php
namespace Psalm\Type\Atomic;

use function implode;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Union;
use Psalm\Storage\MethodStorage;
use function array_map;
use function strtolower;

class TTemplateParam extends \Psalm\Type\Atomic
{
    use HasIntersectionTrait;

    /**
     * @var string
     */
    public $param_name;

    /**
     * @var Union
     */
    public $as;

    /**
     * @var string
     */
    public $defining_class;

    /**
     * @param string $defining_class
     */
    public function __construct(string $param_name, Union $extends, string $defining_class)
    {
        $this->param_name = $param_name;
        $this->as = $extends;
        $this->defining_class = $defining_class;
    }

    public function __toString()
    {
        return $this->param_name;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        if ($this->extra_types) {
            return $this->param_name . ':' . $this->defining_class . '&' . implode('&', $this->extra_types);
        }

        return $this->param_name . ':' . $this->defining_class;
    }

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return $this->as->getId();
    }

    public function getId(bool $nested = false)
    {
        if ($this->extra_types) {
            return '(' . $this->param_name . ':' . $this->defining_class . ' as ' . $this->as->getId()
                . ')&' . implode('&', array_map(function ($type) {
                    return $type->getId(true);
                }, $this->extra_types));
        }

        return ($nested ? '(' : '') . $this->param_name
            . ':' . $this->defining_class
            . ' as ' . $this->as->getId() . ($nested ? ')' : '');
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return null
     */
    public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    ) {
        return null;
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string, string> $aliased_classes
     * @param  string|null   $this_class
     * @param  bool          $use_phpdoc_format
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
            return $this->as->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                $use_phpdoc_format
            );
        }

        $intersection_types = $this->getNamespacedIntersectionTypes(
            $namespace,
            $aliased_classes,
            $this_class,
            $use_phpdoc_format
        );

        return $this->param_name . $intersection_types;
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }

    /**
     * @return void
     */
    public function setFromDocblock()
    {
        $this->from_docblock = true;

        if (!$this->as->isMixed()) {
            $this->as->setFromDocblock();
        }
    }

    /**
     * @param  array<string, array<string, array{Type\Union, 1?:int}>>  $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types, ?Codebase $codebase)
    {
        $this->replaceIntersectionTemplateTypesWithArgTypes($template_types, $codebase);
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

        if ($prevent_template_covariance
            && \substr($this->defining_class, 0, 3) !== 'fn-'
        ) {
            $codebase = $source->getCodebase();

            $class_storage = $codebase->classlike_storage_provider->get($this->defining_class);

            $template_offset = $class_storage->template_types
                ? \array_search($this->param_name, \array_keys($class_storage->template_types), true)
                : false;

            if ($template_offset !== false
                && isset($class_storage->template_covariants[$template_offset])
                && $class_storage->template_covariants[$template_offset]
            ) {
                $method_storage = $source instanceof \Psalm\Internal\Analyzer\MethodAnalyzer
                    ? $source->getFunctionLikeStorage()
                    : null;

                if ($method_storage instanceof MethodStorage
                    && $method_storage->mutation_free
                    && !$method_storage->mutation_free_inferred
                ) {
                    // do nothing
                } else {
                    if (\Psalm\IssueBuffer::accepts(
                        new \Psalm\Issue\InvalidTemplateParam(
                            'Template param ' . $this->param_name . ' of '
                                . $this->defining_class . ' is marked covariant and cannot be used here',
                            $code_location
                        ),
                        $source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        $this->as->check($source, $code_location, $suppressed_issues, $phantom_classes, $inferred);

        $this->checkIntersectionTypes(
            $source,
            $code_location,
            $suppressed_issues,
            $phantom_classes,
            $inferred,
            $prevent_template_covariance
        );
    }
}
