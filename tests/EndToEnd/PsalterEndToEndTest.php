<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Symfony\Component\Process\Process;

use function file_get_contents;
use function file_put_contents;
use function implode;
use function realpath;
use function str_contains;
use function str_replace;
use function strpos;
use function substr_count;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;
use const PHP_EOL;

/**
 * Tests the psalter and psalm-refactor commands, and psalm --alter,
 * by launching a new process as if invoked by a real user.
 */
final class PsalterEndToEndTest extends PsalmEndToEndTestCase
{
    public function testAlter(): void
    {
        $this->runPsalmInit();

        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);

        $this->assertStringContainsString(
            "\n        1 file changed        \n",
            $this->runPsalm(['--alter', '--issues=all'], self::$tmpDir, false, true)['STDOUT'],
        );

        $this->assertSame(0, $this->runPsalm([], self::$tmpDir)['CODE']);
    }

    public function testPsalter(): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);

        (self::newProcess([PHP_BINARY, $this->psalter, '--alter', '--issues=InvalidReturnType'], self::$tmpDir))->mustRun();
        $this->assertSame(0, $this->runPsalm([], self::$tmpDir)['CODE']);
    }

    public function testPsalterAddsPurityAnnotationsWithoutTheConfigFlag(): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(
            self::$tmpDir . '/src/Slug.php',
            "<?php\n\nfunction slug(string \$text): string\n{\n    return strtolower(trim(\$text));\n}\n",
        );

        (self::newProcess([PHP_BINARY, $this->psalter, '--alter', '--issues=MissingPureAnnotation'], self::$tmpDir))->mustRun();

        $this->assertStringContainsString('@psalm-pure', (string) file_get_contents(self::$tmpDir . '/src/Slug.php'));
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideRefactorMoveInvocations')]
    public function testRefactorMovesAMethodIntoAnotherClass(bool $viaPsalm, array $args): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(
            self::$tmpDir . '/src/Moves.php',
            "<?php\n\nnamespace Ns;\n\nclass A\n{\n    public static function foo(): void\n    {\n    }\n}\n\n"
            . "class B\n{\n}\n",
        );

        $process = self::newProcess(
            [PHP_BINARY, $viaPsalm ? $this->psalm : $this->psalmRefactor, ...$args],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringNotContainsString('Cannot locate', $process->getErrorOutput());
        $this->assertStringNotContainsString('No --move or --rename arguments supplied', $process->getErrorOutput());

        $contents = (string) file_get_contents(self::$tmpDir . '/src/Moves.php');
        $this->assertSame(1, substr_count($contents, 'function foo'), $contents);
        $this->assertGreaterThan(
            (int) strpos($contents, 'class B'),
            (int) strpos($contents, 'function foo'),
            'foo should now be declared in B: ' . $contents . $process->getErrorOutput(),
        );
    }

    /**
     * @return iterable<string, array{bool, list<string>}>
     * @psalm-pure
     */
    public static function provideRefactorMoveInvocations(): iterable
    {
        yield 'psalm-refactor with separate values' => [
            false,
            ['--no-cache', '--move', 'Ns\A::foo', '--into', 'Ns\B'],
        ];

        yield 'psalm-refactor with values after an equals sign' => [
            false,
            ['--monochrome', '--move=Ns\A::foo', '--into=Ns\B'],
        ];

        yield 'psalm --refactor' => [
            true,
            ['--refactor', '--move', 'Ns\A::foo', '--into', 'Ns\B'],
        ];

        yield 'leading backslash on --move source' => [
            false,
            ['--no-cache', '--move', '\Ns\A::foo', '--into', 'Ns\B'],
        ];
    }

    public function testRefactorPrintsNoProgressBarOnAPipedStderr(): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(
            self::$tmpDir . '/src/Moves.php',
            "<?php\n\nnamespace Ns;\n\nclass A\n{\n    public static function foo(): void\n    {\n    }\n}\n\n"
            . "class B\n{\n}\n",
        );

        $process = self::newProcess(
            [PHP_BINARY, $this->psalmRefactor, '--no-cache', '--move', 'Ns\A::foo', '--into', 'Ns\B'],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString('Scanning files', $process->getErrorOutput());
        $this->assertDoesNotMatchRegularExpression('/\r(?!\n)/', $process->getErrorOutput());
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideInvalidRefactorDestinations')]
    public function testRefactorReportsAnInvalidDestinationWithoutCrashing(array $args, string $message): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        $source = "<?php\n\nnamespace Ns;\n\nclass A\n{\n    public static int \$p = 1;\n\n    public const K = 1;\n\n"
            . "    public static function foo(): void\n    {\n    }\n}\n\n"
            . "class B\n{\n    public static function bar(): void\n    {\n    }\n}\n\nclass C\n{\n}\n";
        file_put_contents(self::$tmpDir . '/src/Moves.php', $source);

        $process = self::newProcess(
            [PHP_BINARY, $this->psalmRefactor, '--no-progress', '--no-cache', ...$args],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertSame($message . PHP_EOL, $process->getErrorOutput());
        $this->assertSame($source, file_get_contents(self::$tmpDir . '/src/Moves.php'));
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     * @psalm-pure
     */
    public static function provideInvalidRefactorDestinations(): iterable
    {
        yield 'empty --into' => [['--move', 'Ns\A::foo', '--into='], '--into expects a value'];
        yield 'empty --to' => [['--rename', 'Ns\A::foo', '--to='], '--to expects a value'];
        yield 'whitespace --into' => [['--move', 'Ns\A::foo', '--into= '], '--into expects a value'];
        yield 'whitespace --to' => [['--rename', 'Ns\A::foo', '--to= '], '--to expects a value'];
        yield 'missing --into value' => [['--move', 'Ns\A::foo', '--into'], '--into expects a value'];
        yield 'missing --to value' => [['--rename', 'Ns\A::foo', '--to'], '--to expects a value'];
        yield 'empty separate --into value' => [['--move', 'Ns\A::foo', '--into', ''], '--into expects a value'];
        yield 'lone backslash as --to value' => [['--rename', 'Ns\A::foo', '--to', '\\'], '--to expects a value'];
        yield 'lone backslash after --into=' => [['--move', 'Ns\A::foo', '--into=\\'], '--into expects a value'];
        yield '--into followed by another operation' => [
            ['--move', 'Ns\A::foo', '--into', '--rename', 'Ns\B::bar', '--to', 'Ns\C::baz'],
            '--into expects a value',
        ];
        yield '--into followed by an unrelated option' => [
            ['--move', 'Ns\A::foo', '--into', '--no-progress'],
            '--into expects a value',
        ];
        yield 'missing destination class' => [
            ['--move', 'Ns\A::foo', '--into', 'Ns\Missing'],
            'Destination class Ns\Missing doesn’t exist',
        ];
        yield 'missing source class' => [
            ['--move', 'Ns\Missing', '--into', 'Ns\Other'],
            'Source class Ns\Missing doesn’t exist',
        ];
        yield 'destination class already exists' => [
            ['--rename', 'Ns\A', '--to', 'Ns\C'],
            'Destination class Ns\C already exists',
        ];
        yield 'valid mapping followed by a malformed one' => [
            ['--rename', 'Ns\A::foo', '--to', 'Ns\A::baz', '--rename', 'Ns\B', '--to', 'Foo'],
            'Renaming a namespaced class into the global namespace is not supported',
        ];
        yield '--into value that is also a directory' => [
            ['--move', 'Ns\A::foo', '--into', 'src'],
            'Destination class src doesn’t exist',
        ];
        yield 'source that is also a directory' => [
            ['--move', 'src', '--into', 'Ns\B'],
            'Source class src doesn’t exist',
        ];
        yield 'valid mapping followed by a missing destination class' => [
            ['--rename', 'Ns\A::foo', '--to', 'Ns\A::baz', '--move', 'Ns\B::bar', '--into', 'Ns\Missing'],
            'Destination class Ns\Missing doesn’t exist',
        ];
        yield 'leading backslash on --into' => [
            ['--move', 'Ns\A::foo', '--into', '\Ns\Missing'],
            'Destination class Ns\Missing doesn’t exist',
        ];
        yield 'constant renamed to class' => [
            ['--rename', 'Ns\A::K', '--to', 'Ns\A::class'],
            'A class constant must not be called class, it is reserved for class name fetching',
        ];
    }

    /**
     * A malformed mapping is rejected while the arguments are read, so no config is needed.
     *
     * @param list<string> $args
     */
    #[DataProvider('provideMalformedRefactorMappings')]
    public function testRefactorRejectsAMalformedMappingBeforeLoadingTheConfig(array $args, string $message): void
    {
        $process = self::newProcess(
            [PHP_BINARY, $this->psalmRefactor, '--no-progress', '--no-cache', ...$args],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertSame($message . PHP_EOL, $process->getErrorOutput());
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     * @psalm-pure
     */
    public static function provideMalformedRefactorMappings(): iterable
    {
        yield 'class destination for a method' => [
            ['--rename', 'Ns\A::foo', '--to', 'Ns\A'],
            'Destination for Ns\A::foo must be of the form Foo\Bar::baz, got Ns\A',
        ];
        yield 'class destination for a property' => [
            ['--rename', 'Ns\A::$p', '--to', 'Ns\A'],
            'Destination for Ns\A::$p must be of the form Foo\Bar::$baz, got Ns\A',
        ];
        yield 'member destination for a class' => [
            ['--rename', 'Ns\A', '--to', 'Ns\C::foo'],
            'Destination for Ns\A must be a class name like Foo\Bar, got Ns\C::foo',
        ];
        yield 'empty method name' => [
            ['--rename', 'Ns\A::foo', '--to', 'Ns\A::'],
            'Destination for Ns\A::foo must be of the form Foo\Bar::baz, got Ns\A::',
        ];
        yield 'empty property name' => [
            ['--rename', 'Ns\A::$p', '--to', 'Ns\A::$'],
            'Destination for Ns\A::$p must be of the form Foo\Bar::$baz, got Ns\A::$',
        ];
        yield 'empty constant name' => [
            ['--rename', 'Ns\A::K', '--to', 'Ns\A::'],
            'Destination for Ns\A::K must be of the form Foo\Bar::baz, got Ns\A::',
        ];
        yield 'empty class name' => [
            ['--rename', 'Ns\A', '--to', 'Ns\\'],
            'Destination for Ns\A must be a class name like Foo\Bar, got Ns\\',
        ];
        yield 'empty method name in another class' => [
            ['--rename', 'Ns\A::foo', '--to', 'Ns\B::'],
            'Destination for Ns\A::foo must be of the form Foo\Bar::baz, got Ns\B::',
        ];
        yield 'wildcard into a single class' => [
            ['--rename', 'Ns\*', '--to', 'Other\Foo'],
            'Destination for Ns\* must be a wildcard like Foo\*, got Other\Foo',
        ];
        yield 'standalone wildcard source' => [
            ['--rename', '*', '--to', 'Other\*'],
            'Invalid refactor source *',
        ];
        yield 'standalone wildcard destination' => [
            ['--rename', 'Ns\*', '--to', '*'],
            'Destination for Ns\* must be a wildcard like Foo\*, got *',
        ];
        yield 'wildcard destination for a class' => [
            ['--rename', 'Ns\A', '--to', 'Other\*'],
            'Destination for Ns\A must be a class name like Foo\Bar, got Other\*',
        ];
        yield 'malformed mapping followed by a valid one for the same source' => [
            ['--rename', 'Ns\A::foo', '--to', 'Ns\A::', '--rename', 'Ns\A::foo', '--to', 'Ns\A::bar'],
            'Destination for Ns\A::foo must be of the form Foo\Bar::baz, got Ns\A::',
        ];
        yield 'malformed source in a --move list' => [
            ['--move', 'Ns\A::foo, Ns\A::', '--into', 'Ns\B'],
            'Invalid refactor source Ns\A::',
        ];
        yield 'empty namespace segment' => [
            ['--rename', 'Ns\A', '--to', 'Ns\\\\C'],
            'Destination for Ns\A must be a class name like Foo\Bar, got Ns\\\\C',
        ];
        yield 'two member separators' => [
            ['--rename', 'Ns\A::foo', '--to', 'Ns\A::bar::baz'],
            'Destination for Ns\A::foo must be of the form Foo\Bar::baz, got Ns\A::bar::baz',
        ];
        yield 'member name starting with a digit' => [
            ['--rename', 'Ns\A::foo', '--to', 'Ns\A::1bad'],
            'Destination for Ns\A::foo must be of the form Foo\Bar::baz, got Ns\A::1bad',
        ];
        yield 'property destination for a constant' => [
            ['--rename', 'Ns\A::K', '--to', 'Ns\A::$k'],
            'Destination for Ns\A::K must be of the form Foo\Bar::baz, got Ns\A::$k',
        ];
        yield 'malformed source' => [
            ['--rename', 'Ns\A::', '--to', 'Ns\A::bar'],
            'Invalid refactor source Ns\A::',
        ];
        yield 'doubled leading backslash' => [
            ['--rename', 'Ns\A', '--to', '\\\\Ns\C'],
            'Destination for Ns\A must be a class name like Foo\Bar, got \Ns\C',
        ];
        yield '--to value that is also a directory' => [
            ['--rename', 'Ns\A::foo', '--to', 'src'],
            'Destination for Ns\A::foo must be of the form Foo\Bar::baz, got src',
        ];
        yield 'namespaced class renamed to a directory name' => [
            ['--rename', 'Ns\A', '--to', 'src'],
            'Renaming a namespaced class into the global namespace is not supported',
        ];
        yield 'namespaced class renamed into the global namespace' => [
            ['--rename', 'Ns\A', '--to', 'Foo'],
            'Renaming a namespaced class into the global namespace is not supported',
        ];
        yield 'namespaced class renamed to a directory name with --to=' => [
            ['--rename', 'Ns\A', '--to=src'],
            'Renaming a namespaced class into the global namespace is not supported',
        ];
        yield 'namespaced class renamed into the global namespace with --to=' => [
            ['--rename', 'Ns\A', '--to=Foo'],
            'Renaming a namespaced class into the global namespace is not supported',
        ];
        yield 'valid mapping followed by a malformed one' => [
            ['--rename', 'Ns\A::foo', '--to', 'Ns\A::baz', '--rename', 'Ns\B', '--to', 'Foo'],
            'Renaming a namespaced class into the global namespace is not supported',
        ];
        yield 'keyword as class name' => [
            ['--rename', 'Ns\A', '--to', 'Ns\class'],
            'Cannot use class as a class name as it is reserved',
        ];
        yield 'reserved type name as class name' => [
            ['--rename', 'Ns\A', '--to', 'Ns\Int'],
            'Cannot use Int as a class name as it is reserved',
        ];
        yield 'reserved class name as the class of a renamed member' => [
            ['--rename', 'Ns\A::foo', '--to', 'Ns\List::foo'],
            'Cannot use List as a class name as it is reserved',
        ];
        yield 'reserved class name as the class a member is moved into' => [
            ['--move', 'Ns\A::foo', '--into', 'Ns\List'],
            'Cannot use List as a class name as it is reserved',
        ];
    }

    /**
     * @param list<string> $args
     * @param array<string, list<string>> $expected
     * @param array<string, list<string>> $unexpected
     */
    #[DataProvider('provideValidRefactorMappings')]
    public function testRefactorAppliesAValidMapping(array $args, array $expected, array $unexpected): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(
            self::$tmpDir . '/src/A.php',
            "<?php\n\nnamespace Ns;\n\nclass A\n{\n    public static int \$p = 1;\n\n    public const K = 1;\n\n"
            . "    public static function foo(): void\n    {\n    }\n}\n",
        );
        file_put_contents(self::$tmpDir . '/src/B.php', "<?php\n\nnamespace Ns;\n\nclass B\n{\n}\n");
        file_put_contents(
            self::$tmpDir . '/src/f.php',
            "<?php\n\nnamespace App;\n\nuse Ns\A;\n\nfunction f(): void\n{\n    A::foo();\n    echo A::\$p, A::K;\n}\n",
        );

        $process = self::newProcess(
            [PHP_BINARY, $this->psalmRefactor, '--no-progress', '--no-cache', ...$args],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertSame('', $process->getErrorOutput());

        foreach (['A.php', 'B.php', 'f.php'] as $file) {
            $path = self::$tmpDir . '/src/' . $file;
            $contents = (string) file_get_contents($path);

            foreach ($expected[$file] ?? [] as $fragment) {
                $this->assertStringContainsString($fragment, $contents, $file);
            }

            foreach ($unexpected[$file] ?? [] as $fragment) {
                $this->assertStringNotContainsString($fragment, $contents, $file);
            }

            $lint = self::newProcess([PHP_BINARY, '-l', $path], self::$tmpDir);
            $lint->run();
            $this->assertSame(0, $lint->getExitCode(), $file . ': ' . $lint->getOutput() . $contents);
        }
    }

    /**
     * @return iterable<string, array{list<string>, array<string, list<string>>, array<string, list<string>>}>
     * @psalm-pure
     */
    public static function provideValidRefactorMappings(): iterable
    {
        $into_other = [
            'A.php' => ["namespace Other;\n\nclass A\n"],
            'B.php' => ["namespace Other;\n\nclass B\n"],
            'f.php' => ["use Other\A;\n", "    A::foo();\n    echo A::\$p, A::K;\n"],
        ];

        yield 'namespace wildcard' => [['--rename', 'Ns\*', '--to', 'Other\*'], $into_other, []];
        yield 'namespace wildcard with --move' => [['--move', 'Ns\*', '--into', 'Other'], $into_other, []];
        yield 'partial wildcard' => [
            ['--rename', 'Ns\A*', '--to', 'Other\B*'],
            [
                'A.php' => ["namespace Other;\n\nclass B\n"],
                'B.php' => ["namespace Ns;\n\nclass B\n"],
                'f.php' => ["use Other\B;\n", "    B::foo();\n    echo B::\$p, B::K;\n"],
            ],
            [],
        ];
        yield 'method moved and renamed' => [
            ['--rename', 'Ns\A::foo', '--to', 'Ns\B::baz'],
            [
                'A.php' => ['public static int $p = 1;', 'public const K = 1;'],
                'B.php' => ['public static function baz(): void'],
                'f.php' => ['\Ns\B::baz();', 'echo A::$p, A::K;'],
            ],
            ['A.php' => ['function foo'], 'f.php' => ['foo()']],
        ];
        yield 'leading backslash on --to' => [
            ['--rename', 'Ns\A', '--to', '\Ns\C'],
            [
                'A.php' => ["namespace Ns;\n\nclass C\n"],
                'f.php' => ["use Ns\C;\n", "    C::foo();\n    echo C::\$p, C::K;\n"],
            ],
            ['f.php' => ['\\\\']],
        ];
        yield 'leading backslash after --to=' => [
            ['--rename', 'Ns\A', '--to=\Ns\C'],
            [
                'A.php' => ["namespace Ns;\n\nclass C\n"],
                'f.php' => ["use Ns\C;\n", "    C::foo();\n    echo C::\$p, C::K;\n"],
            ],
            ['f.php' => ['\\\\']],
        ];
        yield 'leading backslash on the source' => [
            ['--rename', '\Ns\A::foo', '--to', 'Ns\A::bar'],
            [
                'A.php' => ['public static function bar(): void'],
                'f.php' => ["    A::bar();\n"],
            ],
            ['A.php' => ['function foo'], 'f.php' => ['foo()']],
        ];
        yield 'leading backslash on --into' => [
            ['--move', 'Ns\A::foo', '--into', '\Ns\B'],
            [
                'B.php' => ['public static function foo(): void'],
                'f.php' => ['\Ns\B::foo();'],
            ],
            ['A.php' => ['function foo'], 'f.php' => ['A::foo()']],
        ];
        yield 'leading backslash after --into=' => [
            ['--move', 'Ns\A::foo', '--into=\Ns\B'],
            [
                'B.php' => ['public static function foo(): void'],
                'f.php' => ['\Ns\B::foo();'],
            ],
            ['A.php' => ['function foo'], 'f.php' => ['A::foo()']],
        ];
    }

    public function testPsalmForwardsToPsalterWhenAlterFollowsAPath(): void
    {
        $source = $this->writeAFileWithAMissingReturnType();

        // runPsalm() asserts the exit code is 2 when $shouldFail is true
        $result = $this->runPsalm(
            ['src/A.php', '--alter', '--issues=MissingReturnType', '--dry-run', '--no-cache'],
            self::$tmpDir,
            true,
            false,
        );

        $this->assertStringContainsString('+function a(): int {', $result['STDOUT']);
        $this->assertSame($source, file_get_contents(self::$tmpDir . '/src/A.php'));
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideCommandsWithAnUnrecognisedShortOption')]
    public function testAnUnrecognisedShortOptionIsRejected(string $script, array $args): void
    {
        $this->runPsalmInit();

        $process = self::newProcess([PHP_BINARY, $script, ...$args], self::$tmpDir, self::agentEnvVarsToUnset());
        $process->run();

        $this->assertSame(1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString('Unrecognised argument "-z"', $process->getErrorOutput());
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     * @psalm-pure
     */
    public static function provideCommandsWithAnUnrecognisedShortOption(): iterable
    {
        $issues = ['--issues=MissingReturnType', '--dry-run', '-z'];

        yield 'psalter' => [__DIR__ . '/../../psalter', $issues];
        yield 'psalm --alter' => [__DIR__ . '/../../psalm', ['--alter', ...$issues]];
        yield 'psalm-refactor' => [__DIR__ . '/../../psalm-refactor', ['--move', 'A::b', '--into', 'C', '-z']];
        yield 'psalm-language-server' => [__DIR__ . '/../../psalm-language-server', ['-z']];
        yield 'psalm --language-server' => [__DIR__ . '/../../psalm', ['--language-server', '-z']];
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideCommandsWithAMissingRootDirectory')]
    public function testAMissingRootDirectoryIsShownAsGiven(string $script, array $args, string $message): void
    {
        $this->runPsalmInit();

        $process = self::newProcess([PHP_BINARY, $script, ...$args], self::$tmpDir, self::agentEnvVarsToUnset());
        $process->run();

        $this->assertSame(1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertSame($message . PHP_EOL, $process->getErrorOutput());
    }

    /**
     * @return iterable<string, array{string, list<string>, string}>
     * @psalm-pure
     */
    public static function provideCommandsWithAMissingRootDirectory(): iterable
    {
        $absolute = DIRECTORY_SEPARATOR . 'does-not-exist';

        foreach ([
            'psalm' => [__DIR__ . '/../../psalm', []],
            'psalter' => [__DIR__ . '/../../psalter', ['--issues=all']],
            'psalm-refactor' => [__DIR__ . '/../../psalm-refactor', ['--move', 'A::b', '--into', 'C']],
            'psalm-language-server' => [__DIR__ . '/../../psalm-language-server', []],
        ] as $name => [$script, $args]) {
            yield $name . ' with an absolute path' => [
                $script,
                [...$args, '-r', $absolute],
                'Could not locate root directory ' . $absolute,
            ];
        }
    }

    public function testAMissingRelativeRootDirectoryIsShownFromTheCurrentDirectory(): void
    {
        $this->runPsalmInit();

        $process = self::newProcess(
            [PHP_BINARY, __DIR__ . '/../../psalm', '-r', 'does-not-exist'],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringEndsWith(
            'Could not locate root directory ' . (string) realpath(self::$tmpDir) . DIRECTORY_SEPARATOR
                . 'does-not-exist' . PHP_EOL,
            $process->getErrorOutput(),
        );
    }

    /**
     * @param list<string> $args
     */
    #[DataProvider('provideCommandsWithAnEmptyOptionValue')]
    public function testAnEmptyValueOfAnOptionThatRequiresOneIsRejected(
        string $script,
        array $args,
        string $option,
    ): void {
        $this->runPsalmInit();

        $process = self::newProcess([PHP_BINARY, $script, ...$args], self::$tmpDir, self::agentEnvVarsToUnset());
        $process->run();

        $this->assertSame(1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString('Argument "' . $option . '" requires a value', $process->getErrorOutput());
    }

    /**
     * @return iterable<string, array{string, list<string>, string}>
     * @psalm-pure
     */
    public static function provideCommandsWithAnEmptyOptionValue(): iterable
    {
        yield 'psalm' => [__DIR__ . '/../../psalm', ['--threads='], '--threads'];
        yield 'psalm php-version' => [__DIR__ . '/../../psalm', ['--php-version='], '--php-version'];
        yield 'psalter' => [__DIR__ . '/../../psalter', ['--issues=', '--dry-run'], '--issues'];
        yield 'psalm-refactor' => [__DIR__ . '/../../psalm-refactor', ['--move=', '--into', 'C'], '--move'];
        yield 'psalm-language-server' => [__DIR__ . '/../../psalm-language-server', ['--config='], '--config'];
        yield 'psalm missing value' => [__DIR__ . '/../../psalm', ['--threads'], '--threads'];
        yield 'psalm missing short value' => [__DIR__ . '/../../psalm', ['-c'], '-c'];
        yield 'psalm missing php-version value' => [__DIR__ . '/../../psalm', ['--php-version'], '--php-version'];
    }

    public function testAnOptionalOptionValueMayStayEmpty(): void
    {
        $process = self::newProcess(
            [PHP_BINARY, __DIR__ . '/../../psalm', '--version', '--find-dead-code='],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
    }

    public function testAValidOptionAfterLanguageServerIsAccepted(): void
    {
        $process = self::newProcess(
            [PHP_BINARY, __DIR__ . '/../../psalm', '--language-server', '--version'],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        $this->assertStringContainsString('Psalm ', $process->getOutput());
    }

    public function testPsalterRealRunThatChangesFilesExitsWithCode0(): void
    {
        $process = $this->runPsalterOnAFileWithAMissingReturnType([]);

        $this->assertSame(0, $process->getExitCode());
    }

    public function testPsalterAppliesAFixOnceWhenPathsOverlap(): void
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(self::$tmpDir . '/src/A.php', "<?php\n\nfunction a() {\n    return 1;\n}\n");
        file_put_contents(self::$tmpDir . '/src/B.php', "<?php\n\nfunction b() {\n    return 2;\n}\n");

        $process = self::newProcess(
            [PHP_BINARY, $this->psalter, '--no-cache', '--issues=MissingReturnType', 'src', 'src/A.php'],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->mustRun();

        $this->assertSame(
            "<?php\n\nfunction a(): int {\n    return 1;\n}\n",
            file_get_contents(self::$tmpDir . '/src/A.php'),
        );
        $this->assertSame(
            "<?php\n\nfunction b(): int {\n    return 2;\n}\n",
            file_get_contents(self::$tmpDir . '/src/B.php'),
        );
        $this->assertStringContainsString("\n       2 files changed        \n", $process->getOutput());
    }

    public function testPsalterPrintsTheDiffOfARealRun(): void
    {
        $process = $this->runPsalterOnAFileWithAMissingReturnType([]);
        $output = $process->getOutput();

        $this->assertStringNotContainsString('Altered', $process->getErrorOutput());
        $this->assertStringContainsString("1) src/A.php:3\n", $output);
        $this->assertStringContainsString("\n+function a(): int {\n", $output);
        $this->assertStringContainsString("\n        1 file changed        \n", $output);
    }

    /**
     * @param array<string, string> $env
     */
    #[DataProvider('provideColorEnvironments')]
    public function testPsalterColorsPipedOutputOnlyWhenForced(array $env, bool $expect_color): void
    {
        $output = $this->runPsalterOnAFileWithAMissingReturnType([], $env)->getOutput();

        $this->assertSame($expect_color, str_contains($output, "\e["));
    }

    /**
     * @return iterable<string, array{array<string, string>, bool}>
     * @psalm-pure
     */
    public static function provideColorEnvironments(): iterable
    {
        yield 'piped' => [[], false];
        yield 'FORCE_COLOR' => [['FORCE_COLOR' => '1'], true];
    }

    public function testPsalterNoDiffListsTheChangedFilesWithoutTheDiffAndStillWritesThem(): void
    {
        $process = $this->runPsalterOnAFileWithAMissingReturnType(['--no-diff']);
        $output = $process->getOutput();

        $this->assertStringContainsString('Altered src/A.php', $process->getErrorOutput());

        $this->assertStringStartsWith("1) src/A.php:3\n------------------------------\n", $output);
        $this->assertStringNotContainsString('@@', $output);
        $this->assertStringNotContainsString('+function a()', $output);
        $this->assertStringNotContainsString('-function a()', $output);
        $this->assertStringContainsString("\n        1 file changed        \n", $output);
    }

    /**
     * @param list<string> $extra_args
     * @param array<string, string> $env
     */
    private function runPsalterOnAFileWithAMissingReturnType(array $extra_args, array $env = []): Process
    {
        $this->runPsalmInit();
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(self::$tmpDir . '/src/A.php', "<?php\n\nfunction a() {\n    return 1;\n}\n");

        $process = self::newProcess(
            [PHP_BINARY, $this->psalter, '--no-cache', '--issues=MissingReturnType', ...$extra_args, 'src'],
            self::$tmpDir,
            $env + self::agentEnvVarsToUnset(),
        );
        $process->mustRun();

        $this->assertSame(
            "<?php\n\nfunction a(): int {\n    return 1;\n}\n",
            file_get_contents(self::$tmpDir . '/src/A.php'),
        );

        return $process;
    }

    public function testPsalterFixesTheIssuesAPluginDeclares(): void
    {
        $this->runPsalmInit(1);
        $psalm_xml = (string) file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(self::$tmpDir . '/psalm.xml', str_replace(
            ['<psalm', '</psalm>'],
            [
                '<psalm runTaintAnalysis="false"',
                '<plugins><pluginClass class="Psalm\Tests\Config\Plugin\FixableIssuePlugin" /></plugins></psalm>',
            ],
            $psalm_xml,
        ));
        unlink(self::$tmpDir . '/src/FileWithErrors.php');
        file_put_contents(
            self::$tmpDir . '/src/functions.php',
            "<?php\n\nfunction legacy_call(): int\n{\n    return 1;\n}\n\nfunction modern_call(): int\n{\n    return 2;\n}\n",
        );

        $list = self::newProcess([PHP_BINARY, $this->psalter, '--list-supported-issues'], self::$tmpDir);
        $list->mustRun();
        $this->assertSame(
            implode(',', [...ProjectAnalyzer::getSupportedIssuesToFix(), 'LegacyCall']) . PHP_EOL,
            $list->getOutput(),
        );

        foreach (['a', 'b', 'c'] as $file) {
            file_put_contents(
                self::$tmpDir . '/src/' . $file . '.php',
                "<?php\n\nfunction $file(): int\n{\n    return legacy_call();\n}\n",
            );
        }

        (self::newProcess([PHP_BINARY, $this->psalter, '--issues=LegacyCall', '--threads=2'], self::$tmpDir))->mustRun();

        foreach (['a', 'b', 'c'] as $file) {
            $this->assertStringContainsString(
                'return modern_call();',
                (string) file_get_contents(self::$tmpDir . '/src/' . $file . '.php'),
            );
        }
    }
}
