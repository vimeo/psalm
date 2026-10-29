<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

use function array_key_exists;
use function count;
use function file_get_contents;
use function glob;
use function is_string;
use function min;
use function preg_replace;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

use const DIRECTORY_SEPARATOR;

/**
 * Parameters of the functions declared in stubs must match the call map: a stub overrides
 * the call map, so a renamed or missing parameter breaks named arguments and by-ref handling.
 * Only names, optionality, variadics, by-ref and parameter count are compared, not types.
 */
final class StubCallMapConformityTest extends TestCase
{
    /** @var array<string, string> function => why the stub deliberately differs from the call map */
    private const DEVIATIONS = [
        'fclose' => 'the stub takes the handle by reference so that it is not reported as unused afterwards',
        'pg_escape_bytea' => 'the stub accepts the connection as an optional first argument',
        'pg_escape_identifier' => 'the stub accepts the connection as an optional first argument',
        'pg_escape_literal' => 'the stub accepts the connection as an optional first argument',
        'pg_escape_string' => 'the stub accepts the connection as an optional first argument',
        'cubrid_real_escape_string' => 'the stub does not declare the optional connection',
    ];

    private const STUB_ROOT = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'stubs';

    /**
     * @return array<string, array{string}>
     */
    public static function providerStubFiles(): array
    {
        $files = [];
        foreach ([self::STUB_ROOT, self::STUB_ROOT . DIRECTORY_SEPARATOR . 'extensions'] as $directory) {
            $found = glob($directory . DIRECTORY_SEPARATOR . '*.phpstub');
            if ($found === false) {
                continue;
            }

            foreach ($found as $file) {
                $files[substr($file, strlen(self::STUB_ROOT) + 1)] = [$file];
            }
        }

        return $files;
    }

    /**
     * @return array<string, array{Function_, array<array-key, mixed>}>
     */
    public static function providerStubFunctions(): array
    {
        /** @var array<string, array<array-key, mixed>> $call_map */
        $call_map = require __DIR__ . '/../../../dictionaries/CallMap_86.php';
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        $cases = [];
        foreach (self::providerStubFiles() as [$file]) {
            $stmts = $parser->parse((string) file_get_contents($file), new Collecting());
            if ($stmts === null) {
                continue;
            }

            foreach ((new NodeFinder())->findInstanceOf($stmts, Function_::class) as $function) {
                $name = strtolower($function->name->toString());
                if (array_key_exists($name, $call_map)) {
                    $cases[$name] = [$function, $call_map[$name]];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('providerStubFiles')]
    public function testStubHasNoSyntaxErrors(string $file): void
    {
        $collecting = new Collecting();
        (new ParserFactory())->createForNewestSupportedVersion()
            ->parse((string) file_get_contents($file), $collecting);

        $errors = [];
        foreach ($collecting->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        $this->assertSame([], $errors);
    }

    /**
     * @param array<array-key, mixed> $call_map_entry
     */
    #[DataProvider('providerStubFunctions')]
    public function testStubParametersMatchCallMap(Function_ $function, array $call_map_entry): void
    {
        $name = strtolower($function->name->toString());
        $differences = self::compare($function, $call_map_entry);

        if (isset(self::DEVIATIONS[$name])) {
            $this->assertNotSame(
                [],
                $differences,
                $name . ' no longer deviates from the call map: remove it from DEVIATIONS',
            );

            return;
        }

        $this->assertSame([], $differences, $name . ' differs from the call map');
    }

    /**
     * @psalm-capabilities read-props
     * @param array<array-key, mixed> $call_map_entry
     * @return list<string>
     */
    private static function compare(Function_ $function, array $call_map_entry): array
    {
        $expected = [];
        foreach ($call_map_entry as $key => $_type) {
            if ($key === 0) {
                continue;
            }

            $expected[] = self::parseCallMapParam((string) $key);
        }

        $differences = [];
        if (count($function->params) !== count($expected)) {
            $differences[] = 'parameter count ' . count($function->params) . ' instead of ' . count($expected);
        }

        for ($i = 0, $max = min(count($function->params), count($expected)); $i < $max; $i++) {
            $param = $function->params[$i];
            [$name, $by_ref, $variadic, $optional] = $expected[$i];
            $stub_name = $param->var instanceof Node\Expr\Variable && is_string($param->var->name)
                ? $param->var->name
                : '';

            if ($stub_name !== $name) {
                $differences[] = 'parameter #' . $i . ' is $' . $stub_name . ' instead of $' . $name;
            }

            if ($param->byRef !== $by_ref) {
                $differences[] = 'parameter $' . $stub_name . ' by-ref differs';
            }

            if ($param->variadic !== $variadic) {
                $differences[] = 'parameter $' . $stub_name . ' variadic differs';
            }

            if (!$variadic && ($param->default !== null) !== $optional) {
                $differences[] = 'parameter $' . $stub_name . ' optionality differs';
            }
        }

        return $differences;
    }

    /**
     * Call map keys look like `name`, `name=`, `...name=`, `&r name`, `&w name=`, `&rw name`.
     *
     * @psalm-pure
     * @return array{string, bool, bool, bool}
     */
    private static function parseCallMapParam(string $key): array
    {
        $by_ref = false;
        $variadic = false;
        $optional = false;

        if ($key[0] === '&') {
            $by_ref = true;
            $key = (string) preg_replace('/^&(?:(?:rw|r|w) )?/', '', $key);
        }

        if (str_starts_with($key, '...')) {
            $variadic = true;
            $key = substr($key, 3);
        }

        if (str_ends_with($key, '=')) {
            $optional = true;
            $key = substr($key, 0, -1);
        }

        return [$key, $by_ref, $variadic, $optional];
    }
}
