<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class CliOnlyCallTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'calledFromCliOnlyCode' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    /** @psalm-cli-only */
                    function run(): void {
                        import();
                        array_map(function (int $i): void {
                            import();
                        }, [1]);
                        array_map(fn(int $i) => import(), [1]);
                    }

                    /** @psalm-cli-only */
                    final class ImportCommand {
                        public function __construct() {
                            import();
                        }

                        public function run(): void {
                            import();
                            $this->log();
                            self::make()->log();
                        }

                        public static function make(): self {
                            return new self();
                        }

                        private function log(): void {}
                    }

                    final class Commands {
                        /** @psalm-cli-only */
                        public function run(ImportCommand $command): void {
                            $command->run();
                            new ImportCommand();
                        }
                    }',
            ],
            'calledFromATraitMethodOfACliOnlyClass' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    trait Imports {
                        public function run(): void {
                            import();
                        }
                    }

                    /** @psalm-cli-only */
                    final class ImportCommand {
                        use Imports;
                    }',
            ],
            'guardedByASapiCheck' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    function a(): void {
                        if (PHP_SAPI === "cli") {
                            import();
                        }
                    }

                    function b(): void {
                        if (\PHP_SAPI !== "cli") {
                            exit(1);
                        }

                        import();
                    }

                    function c(): void {
                        if (php_sapi_name() !== "cli" && PHP_SAPI !== "phpdbg") {
                            throw new RuntimeException("Run it from the command line");
                        }

                        import();
                    }

                    function d(): void {
                        assert(PHP_SAPI === "cli");
                        import();
                    }

                    function e(): void {
                        if (in_array(PHP_SAPI, ["cli", "phpdbg"], true)) {
                            import();
                        }
                    }

                    function f(): void {
                        if (PHP_SAPI == "cli") {
                            import();
                        }
                    }

                    function g(): void {
                        PHP_SAPI === "cli" && import();
                    }',
            ],
            'closuresCreatedUnderASapiCheck' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    if (PHP_SAPI === "cli") {
                        array_map(function (int $i): void {
                            import();
                        }, [1]);
                        array_map(fn(int $i) => import(), [1]);
                    }',
            ],
            'topLevelCodeAfterASapiCheck' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    final class ImportCommand {
                        public function run(): void {}
                    }

                    if (PHP_SAPI !== "cli") {
                        exit(1);
                    }

                    (new ImportCommand())->run();',
            ],
            'sapiChecksKeepTheirTypes' => [
                'code' => '<?php
                    function a(): string {
                        if (PHP_SAPI === "cli") {
                            return "a";
                        }

                        if (php_sapi_name() === "phpdbg") {
                            return "b";
                        }

                        $f = function (): string {
                            return PHP_SAPI;
                        };

                        return match (PHP_SAPI) {
                            "cli-server" => "c",
                            default => $f(),
                        };
                    }

                    function b(): string {
                        if (PHP_SAPI !== "cli") {
                            return "";
                        }

                        $sapi = PHP_SAPI;

                        return $sapi;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'functionCalledFromAFunction' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    function run(): void {
                        import();
                    }',
                'error_message' => 'The function import() only runs from the command line',
            ],
            'functionCalledFromTopLevelCode' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    import();',
                'error_message' => 'CliOnlyCall',
            ],
            'functionCalledThroughACallableString' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    "import"();',
                'error_message' => 'CliOnlyCall',
            ],
            'firstClassCallableOfAFunction' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    $import = import(...);',
                'error_message' => 'CliOnlyCall',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'methodOfACliOnlyClass' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    final class ImportCommand {
                        public function run(): void {}
                    }

                    function run(ImportCommand $command): void {
                        $command->run();
                    }',
                'error_message' => 'The method ImportCommand::run() only runs from the command line',
            ],
            'staticMethodOfACliOnlyClass' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    final class ImportCommand {
                        public static function run(): void {}
                    }

                    ImportCommand::run();',
                'error_message' => 'CliOnlyCall',
            ],
            'cliOnlyMethod' => [
                'code' => '<?php
                    final class Importer {
                        /** @psalm-cli-only */
                        public function run(): void {}

                        public function web(): void {
                            $this->run();
                        }
                    }',
                'error_message' => 'The method Importer::run() only runs',
            ],
            'cliOnlyInterfaceMethod' => [
                'code' => '<?php
                    interface Command {
                        /** @psalm-cli-only */
                        public function run(): void;
                    }

                    function run(Command $command): void {
                        $command->run();
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'methodInheritedFromACliOnlyClass' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    abstract class Command {
                        public function run(): void {}
                    }

                    final class WebCommand extends Command {}

                    function run(WebCommand $command): void {
                        $command->run();
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'cliOnlyClassInstantiated' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    final class ImportCommand {}

                    new ImportCommand();',
                'error_message' => 'The class ImportCommand only runs from the command line',
            ],
            'cliOnlyConstructor' => [
                'code' => '<?php
                    final class Importer {
                        /** @psalm-cli-only */
                        public function __construct() {}
                    }

                    new Importer();',
                'error_message' => 'The method Importer::__construct() only runs',
            ],
            'constructorOfACliOnlyParent' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    abstract class Command {
                        public function __construct() {}
                    }

                    final class WebCommand extends Command {
                        public function __construct() {
                            parent::__construct();
                        }
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'cloneOfACliOnlyClass' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    final class ImportCommand {
                        public function __clone() {}
                    }

                    function copyCommand(ImportCommand $command): ImportCommand {
                        return clone $command;
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'calledFromATraitMethodOfAClassThatIsNotCliOnly' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    trait Imports {
                        public function run(): void {
                            import();
                        }
                    }

                    /** @psalm-cli-only */
                    final class ImportCommand {
                        use Imports;
                    }

                    final class ImportController {
                        use Imports;
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'calledAfterTheGuardedBranch' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    function run(): void {
                        if (PHP_SAPI === "cli") {
                            import();
                        }

                        import();
                    }',
                'error_message' => 'CliOnlyCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:25',
            ],
            'calledInTheElseBranch' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    if (PHP_SAPI === "cli") {
                        echo "cli";
                    } else {
                        import();
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'builtinWebServerIsNotTheCommandLine' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    if (PHP_SAPI === "cli-server") {
                        import();
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'guardOfAnotherFunction' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    if (PHP_SAPI !== "cli") {
                        exit(1);
                    }

                    function run(): void {
                        import();
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'closureCreatedOutsideTheGuard' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    $import = function (): void {
                        import();
                    };

                    if (PHP_SAPI === "cli") {
                        $import();
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'calledFromAFunctionCalledByCliOnlyCode' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    function helper(): void {
                        import();
                    }

                    /** @psalm-cli-only */
                    function run(): void {
                        helper();
                    }',
                'error_message' => 'CliOnlyCall',
            ],
        ];
    }
}
