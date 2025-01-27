<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\Aliases;
use Psalm\Internal\Type\TypeAlias;
use Psalm\Issue\CodeIssue;
use Psalm\Type\Union;

final class FileStorage
{
    use CustomMetadataTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @var array<lowercase-string, string>
     */
    public array $classlikes_in_file = [];

    /**
     * @var array<lowercase-string, string>
     */
    public array $referenced_classlikes = [];

    /**
     * @var array<lowercase-string, string>
     */
    public array $required_classes = [];

    /**
     * @var array<lowercase-string, string>
     */
    public array $required_interfaces = [];

    /**
     * @var array<string, FunctionStorage>
     */
    public array $functions = [];

    /** @var array<string, string> */
    public array $declaring_function_ids = [];

    /**
     * @var array<string, Union>
     */
    public array $constants = [];

    /** @var array<string, string> */
    public array $declaring_constants = [];

    /** @var array<lowercase-string, string> */
    public array $required_file_paths = [];

    /** @var array<lowercase-string, string> */
    public array $required_by_file_paths = [];

    public bool $populated = false;

    public bool $deep_scan = false;

    public bool $has_extra_statements = false;

    public string $hash = '';

    public bool $has_visitor_issues = false;

    /**
     * @var list<CodeIssue>
     */
    public array $docblock_issues = [];

    /**
     * @var array<string, TypeAlias>
     */
    public array $type_aliases = [];

    /**
     * @var array<string, string>
     */
    public array $classlike_aliases = [];

    public ?Aliases $aliases = null;

    /** @var Aliases[] */
    public array $namespace_aliases = [];

    public function __construct(public string $file_path)
    {
    }
}
