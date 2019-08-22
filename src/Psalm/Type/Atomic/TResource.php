<?php
namespace Psalm\Type\Atomic;

use Psalm\CodeLocation;
use Psalm\StatementsSource;

class TResource extends \Psalm\Type\Atomic
{
    public function __toString()
    {
        return 'resource';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'resource';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return null|string
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

    public function canBeFullyExpressedInPhp()
    {
        return false;
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

        if (!$this->from_docblock) {
            if (\Psalm\IssueBuffer::accepts(
                new \Psalm\Issue\ReservedWord(
                    '\'resource\' is a reserved word',
                    $code_location,
                    'resource'
                ),
                $source->getSuppressedIssues()
            )) {
                // fall through
            }
        }
    }
}
