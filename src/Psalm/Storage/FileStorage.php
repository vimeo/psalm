<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\Aliases;
use Psalm\Internal\Type\TypeAlias;
use Psalm\Issue\CodeIssue;
use Psalm\Type\Union;

/**
 * @api
 */
final class FileStorage
{
    use CustomMetadataTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @var array<int, int> class name id => class name id (same id)
     */
    public array $classlikes_in_file = [];

    /**
     * @var array<int, int> class name id => class name id (same id)
     */
    public array $referenced_classlikes = [];

    /**
     * @var array<int, int> class name id => class name id (same id)
     */
    public array $required_classes = [];

    /**
     * @var array<int, int> interface name id => interface name id (same id)
     */
    public array $required_interfaces = [];

    /**
     * @var array<int, FunctionStorage> function id => storage
     */
    public array $functions = [];

    /** @var array<int, string> function id => lowercase file path */
    public array $declaring_function_ids = [];

    /**
     * @var array<int, Union> constant name id => type
     */
    public array $constants = [];

    /** @var array<int, string> constant name id => file path */
    public array $declaring_constants = [];

    /** @var array<lowercase-string, string> */
    public array $required_file_paths = [];

    /** @var array<lowercase-string, string> */
    public array $required_by_file_paths = [];

    public bool $populated = false;

    public bool $deep_scan = false;

    public bool $has_extra_statements = false;

    public bool $has_visitor_issues = false;

    /**
     * @var list<CodeIssue>
     */
    public array $docblock_issues = [];

    /**
     * @var array<int, TypeAlias> alias name id => alias
     */
    public array $type_aliases = [];

    /**
     * @var array<int, int> alias class name id => aliased class name id
     */
    public array $classlike_aliases = [];

    public ?Aliases $aliases = null;

    /** @var Aliases[] */
    public array $namespace_aliases = [];

    /**
     * @psalm-mutation-free
     */
    public function __construct(public string $file_path)
    {
    }
}
