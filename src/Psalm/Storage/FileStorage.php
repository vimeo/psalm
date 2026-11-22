<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\Aliases;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
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
     * The classes whose constants and imported type aliases the declarations of the file use (class constants, enum
     * case values, parameter defaults, `@psalm-import-type`): a change to one of them changes those declarations where
     * they are read, so the file depends on it as on a parent
     *
     * @var array<lowercase-string, string>
     */
    public array $classlikes_used_in_declarations = [];

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

    /**
     * The constants declared here with @deprecated or #[\Deprecated]
     *
     * @var array<string, true>
     */
    public array $deprecated_constants = [];

    /**
     * The values of the constants whose types depend on classes, e.g. enum cases, resolved when they are fetched
     *
     * @var array<string, UnresolvedConstantComponent>
     */
    public array $unresolved_constants = [];

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
     * Issues found while scanning the file, reported when it is analysed
     *
     * @internal
     * @var list<CodeIssue>
     */
    public array $scan_issues = [];

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

    /**
     * @psalm-mutation-free
     */
    public function __construct(public string $file_path)
    {
    }
}
