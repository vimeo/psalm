<?php
namespace Psalm\Type\Atomic;

use function implode;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic;
use function substr;
use function array_map;
use function strtolower;

class TNamedObject extends Atomic
{
    use HasIntersectionTrait;

    /**
     * @var string
     */
    public $value;

    /**
     * @param string $value the name of the object
     */
    public function __construct($value)
    {
        if ($value[0] === '\\') {
            $value = substr($value, 1);
        }

        $this->value = $value;
    }

    public function __toString()
    {
        return $this->getKey();
    }

    /**
     * @return string
     */
    public function getKey()
    {
        if ($this->extra_types) {
            return $this->value . '&' . implode('&', $this->extra_types);
        }

        return $this->value;
    }

    public function getId(bool $nested = false)
    {
        if ($this->extra_types) {
            return $this->value . '&' . implode(
                '&',
                array_map(
                    function ($type) {
                        return $type->getId(true);
                    },
                    $this->extra_types
                )
            );
        }

        return $this->value;
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
        if ($this->value === 'static') {
            return 'static';
        }

        $intersection_types = $this->getNamespacedIntersectionTypes(
            $namespace,
            $aliased_classes,
            $this_class,
            $use_phpdoc_format
        );

        return Type::getStringFromFQCLN($this->value, $namespace, $aliased_classes, $this_class, true)
            . $intersection_types;
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string, string> $aliased_classes
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
        if ($this->value === 'static') {
            return null;
        }

        return $this->toNamespacedString($namespace, $aliased_classes, $this_class, false);
    }

    public function canBeFullyExpressedInPhp()
    {
        return $this->value !== 'static';
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
     * @return list<Type\Atomic\TTemplateParam>
     */
    public function getTemplateTypes() : array
    {
        return $this->getIntersectionTemplateTypes();
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

        $codebase = $source->getCodebase();

        if ($code_location instanceof CodeLocation\DocblockTypeLocation
            && $codebase->store_node_types
            && $this->offset_start !== null
            && $this->offset_end !== null
        ) {
            $codebase->analyzer->addOffsetReference(
                $source->getFilePath(),
                $code_location->raw_file_start + $this->offset_start,
                $code_location->raw_file_start + $this->offset_end,
                $this->value
            );
        }

        if (!isset($phantom_classes[\strtolower($this->value)]) &&
            \Psalm\Internal\Analyzer\ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $source,
                $this->value,
                $code_location,
                null,
                $suppressed_issues,
                $inferred,
                false,
                true,
                $this->from_docblock
            ) === false
        ) {
            return false;
        }

        $fq_class_name_lc = strtolower($this->value);

        if ($codebase->classlike_storage_provider->has($fq_class_name_lc)
            && $source->getFQCLN() !== $this->value
        ) {
            $class_storage = $codebase->classlike_storage_provider->get($fq_class_name_lc);

            if ($class_storage->deprecated) {
                if (\Psalm\IssueBuffer::accepts(
                    new \Psalm\Issue\DeprecatedClass(
                        'Class ' . $this->value . ' is marked as deprecated',
                        $code_location,
                        $this->value
                    ),
                    $source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }

        $this->checkIntersectionTypes(
            $source,
            $code_location,
            $suppressed_issues,
            $phantom_classes,
            $inferred,
            $prevent_template_covariance
        );

        if ($this instanceof TGenericObject) {
            $this->checkGenericParams(
                $source,
                $code_location,
                $suppressed_issues,
                $phantom_classes,
                $inferred,
                $prevent_template_covariance
            );
        }

        $this->checked = true;
    }
}
