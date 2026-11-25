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
     * Whether the file declares strict_types
     */
    public bool $strict_types = false;

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

    /**
     * The names a class_alias() call of the file gives a class, whatever it aliases, and even in code psalm skips
     *
     * @var array<lowercase-string, true>
     */
    public array $possible_class_aliases = [];

    /**
     * Whether the file may call class_alias() with a name psalm doesn't know: an alias that isn't a literal, an
     * unpacked argument, or class_alias as a callable
     */
    public bool $has_dynamic_class_alias = false;

    /**
     * The lowercase names of the global constants the `const` statements and the define() calls of the file declare,
     * even in code psalm skips, and how many times
     *
     * @var array<lowercase-string, positive-int>
     */
    public array $declared_constants = [];

    /**
     * Whether the file may call define() with a name psalm doesn't know: a name that isn't a literal, an unpacked
     * argument, or define as a callable
     */
    public bool $has_dynamic_define = false;

    /**
     * The variables of the global scope the code of the file may write from elsewhere: those it declares `global`
     * and those it reads in `$GLOBALS`
     *
     * @var array<string, true>
     */
    public array $global_variables = [];

    /**
     * Whether the code of the file may write a variable of the global scope psalm doesn't know: `global $$name`,
     * `$GLOBALS[$name]` or `$GLOBALS` itself
     */
    public bool $has_dynamic_global = false;

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
