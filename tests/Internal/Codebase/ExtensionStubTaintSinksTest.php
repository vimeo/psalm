<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Psalm\Type\TaintKind;

use function array_values;
use function assert;
use function basename;
use function file_get_contents;
use function glob;
use function preg_match_all;
use function strtolower;

use const PREG_SET_ORDER;

/**
 * The stubs of stubs/extensions only load when the project requires their extension, while the functions and classes
 * they declare still come from the call map: their sinks must also be in dictionaries/InternalTaintSinkMap.php.
 */
final class ExtensionStubTaintSinksTest extends TestCase
{
    /**
     * @return array<string, array{string, int, string, int}>
     */
    public static function providerExtensionStubSinks(): array
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $finder = new NodeFinder();
        $sinks = [];

        $files = glob(__DIR__ . '/../../../stubs/extensions/*.phpstub');
        assert($files !== false);

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            assert($contents !== false);
            $stmts = $parser->parse($contents) ?? [];

            $traverser = new NodeTraverser(new NameResolver());
            $stmts = $traverser->traverse($stmts);

            foreach ($finder->findInstanceOf($stmts, ClassLike::class) as $class) {
                if ($class->namespacedName === null) {
                    continue;
                }

                foreach ($class->getMethods() as $method) {
                    $method_id = $class->namespacedName->toString() . '::' . $method->name->toString();
                    self::addSinks($sinks, basename($file), $method_id, $method);
                }
            }

            foreach ($finder->findInstanceOf($stmts, Function_::class) as $function) {
                assert($function->namespacedName !== null);
                self::addSinks($sinks, basename($file), $function->namespacedName->toString(), $function);
            }
        }

        return $sinks;
    }

    /**
     * @param array<string, array{string, int, string, int}> $sinks
     */
    private static function addSinks(
        array &$sinks,
        string $file,
        string $function_id,
        ClassMethod|Function_ $function,
    ): void {
        $doc = $function->getDocComment();
        if ($doc === null) {
            return;
        }

        // conditional sinks depend on the arguments, which the map can't express
        preg_match_all('/@psalm-taint-sink\s+([a-z_]+)\s+\$(\w+)\s*$/m', $doc->getText(), $matches, PREG_SET_ORDER);

        foreach ($matches as [, $kind, $param_name]) {
            foreach (array_values($function->getParams()) as $offset => $param) {
                if ($param->var instanceof Variable && $param->var->name === $param_name) {
                    $sinks[$file . ' ' . $function_id . ' $' . $param_name . ' ' . $kind]
                        = [$function_id, $offset, $kind, TaintKind::TAINT_NAMES[$kind]];
                }
            }
        }
    }

    #[DataProvider('providerExtensionStubSinks')]
    public function testSinkIsInTheMap(string $function_id, int $offset, string $kind, int $taints): void
    {
        /** @var array<string, list<int>> $map */
        $map = require __DIR__ . '/../../../dictionaries/InternalTaintSinkMap.php';
        $lowercase_map = [];
        foreach ($map as $key => $value) {
            $lowercase_map[strtolower($key)] = $value;
        }

        $this->assertSame(
            $taints,
            ($lowercase_map[strtolower($function_id)][$offset] ?? 0) & $taints,
            "InternalTaintSinkMap.php is missing the $kind sink of the parameter $offset of $function_id",
        );
    }
}
