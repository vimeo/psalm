<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Config\ProjectFileFilter;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\IncludeCollector;
use Psalm\Progress\Progress;
use SimpleXMLElement;

use function array_intersect_key;
use function array_keys;
use function dirname;
use function file_get_contents;
use function getcwd;
use function ltrim;
use function preg_match_all;
use function str_contains;
use function strtolower;

use const DIRECTORY_SEPARATOR;

final class TestConfig extends Config
{
    public const INIT_PROJECT_FILES_NOW = true;
    private static ?ProjectFileFilter $cached_project_files = null;

    /** @var array<string, array{array<lowercase-string, true>, list<lowercase-string>}> */
    private static array $extension_symbols = [];

    /** @var array<string, array<lowercase-string, list<lowercase-string>>> */
    private static array $functions_by_returned_class = [];

    public function __construct()
    {
        parent::__construct();

        $this->throw_exception = true;
        $this->use_docblock_types = true;
        $this->level = 1;
        $this->cache_directory = null;
        $this->ignore_internal_falsable_issues = true;
        $this->ignore_internal_nullable_issues = true;
        $this->ensure_override_attribute = false;
        $this->strict_binary_operands = false;

        $this->base_dir = (string) getcwd();

        if (!self::$cached_project_files) {
            self::$cached_project_files = ProjectFileFilter::loadFromXMLElement(
                new SimpleXMLElement($this->getContents()),
                $this->base_dir,
                true,
            );
        }

        $this->project_files = self::$cached_project_files;
        $this->setIncludeCollector(new IncludeCollector());

        $this->collectPredefinedConstants();
        $this->collectPredefinedFunctions();
    }

    /**
     * Scanning every extension stub took about two thirds of a simple test case, and few cases use an
     * extension, so an extension the config leaves unset is enabled only if the scanned files use it.
     */
    #[Override]
    public function visitStubFiles(Codebase $codebase, ?Progress $progress = null): void
    {
        $unset_extensions = array_keys($this->php_extensions, null, true);
        if ($unset_extensions !== []) {
            $identifiers = [];
            foreach ([...$codebase->scanner->getScannedFiles(), ...$this->getStubFiles()] as $file_path => $_) {
                preg_match_all(
                    '/[a-z_\x80-\xff][a-z0-9_\x80-\xff]*/i',
                    $codebase->file_provider->getContents($file_path),
                    $matches,
                );
                foreach ($matches[0] as $identifier) {
                    $identifiers[strtolower($identifier)] = true;
                }
            }

            $functions_by_returned_class = self::getFunctionsByReturnedClass($codebase);
            foreach ($unset_extensions as $extension) {
                [$symbols, $classes] = self::getExtensionSymbols($extension);
                $used = array_intersect_key($symbols, $identifiers) !== [];
                foreach ($classes as $class) {
                    foreach ($functions_by_returned_class[$class] ?? [] as $function) {
                        $used = $used || isset($identifiers[$function]);
                    }
                }
                $this->php_extensions[$extension] = $used;
            }
        }

        parent::visitStubFiles($codebase, $progress);
    }

    /**
     * What code using an extension has to mention: the name of a global class-like, function or
     * constant of its stub, or the root namespace of a namespaced one. The class-likes are returned
     * too, as code can also get one from a builtin function without naming it.
     *
     * @return array{array<lowercase-string, true>, list<lowercase-string>}
     */
    private static function getExtensionSymbols(string $extension): array
    {
        if (isset(self::$extension_symbols[$extension])) {
            return self::$extension_symbols[$extension];
        }

        $stub_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'extensions'
            . DIRECTORY_SEPARATOR . $extension . '.phpstub';
        $stmts = (new ParserFactory())->createForNewestSupportedVersion()
            ->parse((string) file_get_contents($stub_path)) ?? [];
        $symbols = [];
        $classes = [];
        foreach ($stmts as $stmt) {
            $namespace = $stmt instanceof Stmt\Namespace_ && $stmt->name !== null ? $stmt->name : null;
            if ($namespace !== null) {
                $symbols[strtolower($namespace->getFirst())] = true;
            }
            foreach ($stmt instanceof Stmt\Namespace_ ? $stmt->stmts : [$stmt] as $declaration) {
                if ($declaration instanceof Stmt\ClassLike && $declaration->name !== null) {
                    $classes[] = strtolower(Name::concat($namespace, $declaration->name->name)?->toString() ?? '');
                }
                if ($namespace === null) {
                    foreach (self::getDeclaredNames($declaration) as $name) {
                        $symbols[strtolower($name)] = true;
                    }
                }
            }
        }

        return self::$extension_symbols[$extension] = [$symbols, $classes];
    }

    /**
     * @return list<string>
     */
    private static function getDeclaredNames(Stmt $stmt): array
    {
        if ($stmt instanceof Stmt\ClassLike || $stmt instanceof Stmt\Function_) {
            return $stmt->name !== null ? [$stmt->name->name] : [];
        }
        if ($stmt instanceof Stmt\Const_) {
            $names = [];
            foreach ($stmt->consts as $const) {
                $names[] = $const->name->name;
            }
            return $names;
        }
        // define() calls, also when guarded by `if (!defined(...))`
        $names = [];
        foreach ((new NodeFinder())->findInstanceOf($stmt, FuncCall::class) as $call) {
            if ($call->name instanceof Name
                && $call->name->toLowerString() === 'define'
                && isset($call->args[0])
                && $call->args[0] instanceof Node\Arg
                && $call->args[0]->value instanceof String_
            ) {
                $names[] = $call->args[0]->value->value;
            }
        }
        return $names;
    }

    /**
     * The builtin functions of the CallMap of the analysed PHP version by each class-like their return type names
     *
     * @return array<lowercase-string, list<lowercase-string>>
     */
    private static function getFunctionsByReturnedClass(Codebase $codebase): array
    {
        $version = $codebase->getMajorAnalysisPhpVersion() . '.' . $codebase->getMinorAnalysisPhpVersion();
        if (isset(self::$functions_by_returned_class[$version])) {
            return self::$functions_by_returned_class[$version];
        }

        $functions_by_returned_class = [];
        foreach (InternalCallMapHandler::getCallMap() as $function => [$return_type]) {
            if (str_contains($function, '::')) {
                continue;
            }
            preg_match_all('/[a-z_\\\\][a-z0-9_\\\\]*/i', $return_type, $matches);
            foreach ($matches[0] as $class) {
                $functions_by_returned_class[strtolower(ltrim($class, '\\'))][] = strtolower($function);
            }
        }

        return self::$functions_by_returned_class[$version] = $functions_by_returned_class;
    }

    /**
     * @psalm-pure
     */
    protected function getContents(): string
    {
        return '<?xml version="1.0"?>
                <projectFiles>
                    <directory name="src" />
                    <file name="tests/somefile.php" />
                    <ignoreFiles>
                        <directory name="src/Psalm/Internal/Stubs" />
                    </ignoreFiles>
                </projectFiles>';
    }

    /**
     * @return false
     * @psalm-pure
     */
    #[Override]
    public function getComposerFilePathForClassLike(string $fq_classlike_name): bool
    {
        return false;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getProjectDirectories(): array
    {
        return [];
    }
}
