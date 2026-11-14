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

    /** A class whose instances only exist in command-line processes */
    private const REQUEST = '
                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }
                    }
';

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
            'guardedByAFunctionAssertingTheSapi' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    /** @psalm-assert "cli" PHP_SAPI */
                    function requireCli(): void {
                        if (PHP_SAPI !== "cli") {
                            throw new LogicException("Run it from the command line");
                        }
                    }

                    /** @psalm-assert "cli"|"phpdbg" php_sapi_name() */
                    function requireCommandLine(): void {
                        if (!in_array(php_sapi_name(), ["cli", "phpdbg"], true)) {
                            throw new LogicException("Run it from the command line");
                        }
                    }

                    /** @psalm-assert-if-true "cli" PHP_SAPI */
                    function isCli(): bool {
                        return PHP_SAPI === "cli";
                    }

                    /** @phpstan-assert-if-false "cli" \PHP_SAPI */
                    function isWeb(): bool {
                        return PHP_SAPI !== "cli";
                    }

                    function a(): void {
                        requireCli();
                        import();
                        array_map(function (int $i): void {
                            import();
                        }, [1]);
                        array_map(fn(int $i) => import(), [1]);
                    }

                    function b(): void {
                        requireCommandLine();
                        import();
                    }

                    function c(): void {
                        if (isCli()) {
                            import();
                        }
                    }

                    function d(): void {
                        if (!isWeb()) {
                            import();
                        }
                    }

                    function e(): void {
                        if (isWeb()) {
                            return;
                        }

                        import();
                    }

                    requireCli();
                    import();',
            ],
            'guardedByAMethodAssertingTheSapi' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    final class Sapi {
                        /** @psalm-assert "cli" PHP_SAPI */
                        public static function requireCli(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        /** @psalm-assert-if-true "cli" PHP_SAPI */
                        public static function isCli(): bool {
                            return PHP_SAPI === "cli";
                        }

                        /** @psalm-assert "cli" PHP_SAPI */
                        public function check(): void {
                            self::requireCli();
                        }

                        /** @psalm-assert-if-true "cli" PHP_SAPI */
                        public function runsFromTheCommandLine(): bool {
                            return self::isCli();
                        }

                        /** @psalm-assert-if-false "cli" PHP_SAPI */
                        public function runsOnTheWeb(): bool {
                            return !self::isCli();
                        }
                    }

                    function a(): void {
                        Sapi::requireCli();
                        import();
                    }

                    function b(): void {
                        if (Sapi::isCli()) {
                            import();
                        }
                    }

                    function c(Sapi $sapi): void {
                        $sapi->check();
                        import();
                    }

                    function d(Sapi $sapi): void {
                        if ($sapi->runsFromTheCommandLine()) {
                            import();
                        }
                    }

                    function e(Sapi $sapi): void {
                        if ($sapi->runsOnTheWeb()) {
                            return;
                        }

                        import();
                    }',
            ],
            'parameterNamedPhpSapiStaysTheTargetOfItsAssertion' => [
                'code' => '<?php
                    /** @psalm-assert "cli" $PHP_SAPI */
                    function requireCli(string $PHP_SAPI): void {
                        if ($PHP_SAPI !== "cli") {
                            throw new LogicException("Run it from the command line");
                        }
                    }

                    $sapi = PHP_SAPI;
                    requireCli($sapi);',
                'assertions' => [
                    '$sapi===' => "'cli'",
                ],
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
            'codeRequiringACliOnlyInstanceRunsFromTheCommandLine' => [
                'code' => '<?php' . self::REQUEST . '
                    /** @psalm-cli-only */
                    function import(): void {}

                    interface HasArgs {}

                    trait Logs {
                        public function log(): void {
                            import();
                        }
                    }

                    /** @psalm-cli-only-instances */
                    final class ArgsRequest implements HasArgs {
                        use Logs;

                        public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }

                            import();
                        }

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function run(): void {
                            import();
                            array_map(fn(int $i) => import(), [1]);
                        }

                        public function __destruct() {
                            import();
                        }
                    }

                    function withARequest(Request $request): void {
                        import();
                        array_map(function (int $i): void {
                            import();
                        }, [1]);
                    }

                    function withAnIntersection(HasArgs&ArgsRequest $request): void {
                        import();
                    }

                    function withARequestByReference(Request &$request): void {
                        import();
                    }

                    function withOtherParameters(int $i, Request $request, ?string $s = null): void {
                        import();
                    }

                    final class Commands {
                        public function run(Request $request): void {
                            import();
                        }

                        public static function make(Request $request): void {
                            import();
                        }
                    }

                    /** @param list<Request> $requests */
                    function each(array $requests): void {
                        array_map(function (Request $request): void {
                            import();
                        }, $requests);
                        array_map(fn(Request $request) => import(), $requests);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'callersOfCliOnlyCodeRequiringACliOnlyInstance' => [
                'code' => '<?php' . self::REQUEST . '
                    /** @psalm-cli-only */
                    function import(Request $request): void {}

                    final class Importer {
                        /** @psalm-cli-only */
                        public function run(Request $request): void {}
                    }

                    /** @psalm-cli-only */
                    final class ImportCommand {
                        public function __construct(Request $request) {}
                    }

                    /**
                     * @psalm-cli-only
                     * @psalm-cli-only-instances
                     */
                    final class CliRequest {
                        public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __unserialize(array $data): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function run(): void {}
                    }

                    function handle(?Request $request, Importer $importer, ?CliRequest $cli_request): void {
                        if ($request !== null) {
                            import($request);
                            $importer->run($request);
                            new ImportCommand($request);
                        }

                        $cli_request?->run();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'cliOnlyInstancesGuardedByAnAssertionOfTheSapi' => [
                'code' => '<?php
                    /** @psalm-assert "cli" PHP_SAPI */
                    function requireCli(): void {
                        if (PHP_SAPI !== "cli") {
                            throw new LogicException("Run it from the command line");
                        }
                    }

                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __construct(private string $path) {
                            requireCli();
                        }

                        /** @param array{path: string} $data */
                        public function __unserialize(array $data): void {
                            requireCli();
                            $this->path = $data["path"];
                        }

                        /** @return array{path: string} */
                        public function __serialize(): array {
                            return ["path" => $this->path];
                        }
                    }',
            ],
            'cliOnlyInstancesThatCanNotBeUnserialized' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __construct() {
                            if (php_sapi_name() !== "cli") {
                                exit(1);
                            }
                        }

                        public function __wakeup(): never {
                            throw new LogicException("A request can not be unserialized");
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
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
            'calledOutsideTheBranchOfAFunctionAssertingTheSapiIfTrue' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    /** @psalm-assert-if-true "cli" PHP_SAPI */
                    function isCli(): bool {
                        return PHP_SAPI === "cli";
                    }

                    function run(): void {
                        if (isCli()) {
                            import();
                        }

                        import();
                    }',
                'error_message' => 'CliOnlyCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:15:25',
            ],
            'calledInTheElseBranchOfAFunctionAssertingTheSapiIfTrue' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    /** @psalm-assert-if-true "cli" PHP_SAPI */
                    function isCli(): bool {
                        return PHP_SAPI === "cli";
                    }

                    if (!isCli()) {
                        import();
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'calledWhenAFunctionAssertingTheSapiIfFalseReturnsTrue' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    /** @psalm-assert-if-false "cli" PHP_SAPI */
                    function isWeb(): bool {
                        return PHP_SAPI !== "cli";
                    }

                    if (isWeb()) {
                        import();
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'calledAfterAFunctionAssertingAnotherSapi' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    /** @psalm-assert "cli-server" PHP_SAPI */
                    function requireBuiltinWebServer(): void {
                        if (PHP_SAPI !== "cli-server") {
                            throw new LogicException("Run it in the builtin web server");
                        }
                    }

                    requireBuiltinWebServer();
                    import();',
                'error_message' => 'CliOnlyCall',
            ],
            'calledAfterAFunctionAssertingTheSapiIsNotTheCommandLine' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    /** @psalm-assert !"cli" PHP_SAPI */
                    function requireWeb(): void {
                        if (PHP_SAPI === "cli") {
                            throw new LogicException("Run it on the web");
                        }
                    }

                    requireWeb();
                    import();',
                'error_message' => 'CliOnlyCall',
            ],
            'calledBeforeAFunctionAssertingTheSapi' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    /** @psalm-assert "cli" PHP_SAPI */
                    function requireCli(): void {
                        if (PHP_SAPI !== "cli") {
                            throw new LogicException("Run it from the command line");
                        }
                    }

                    $import = function (): void {
                        import();
                    };

                    requireCli();
                    $import();',
                'error_message' => 'CliOnlyCall',
            ],
            'calledOutsideTheBranchOfAStaticMethodAssertingTheSapiIfTrue' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    final class Sapi {
                        /** @psalm-assert-if-true "cli" PHP_SAPI */
                        public static function isCli(): bool {
                            return PHP_SAPI === "cli";
                        }
                    }

                    if (!Sapi::isCli()) {
                        import();
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'calledAfterAnInstanceMethodAssertingAnotherSapi' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    final class Sapi {
                        /** @psalm-assert "fpm-fcgi" PHP_SAPI */
                        public function requireFpm(): void {
                            if (PHP_SAPI !== "fpm-fcgi") {
                                throw new LogicException("Run it in FPM");
                            }
                        }
                    }

                    function run(Sapi $sapi): void {
                        $sapi->requireFpm();
                        import();
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'calledWhenAnInstanceMethodAssertingTheSapiIfFalseReturnsTrue' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    final class Sapi {
                        /** @psalm-assert-if-false "cli" PHP_SAPI */
                        public function runsOnTheWeb(): bool {
                            return PHP_SAPI !== "cli";
                        }
                    }

                    function run(Sapi $sapi): void {
                        if ($sapi->runsOnTheWeb()) {
                            import();
                        }
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
            'nullableCliOnlyInstanceParameter' => [
                'code' => '<?php' . self::REQUEST . '
                    /** @psalm-cli-only */
                    function import(): void {}

                    function run(?Request $request): void {
                        import();
                    }',
                'error_message' => 'The function import() only runs from the command line',
            ],
            'optionalCliOnlyInstanceParameter' => [
                'code' => '<?php' . self::REQUEST . '
                    /** @psalm-cli-only */
                    function import(): void {}

                    function run(Request $request = new Request()): void {
                        import();
                    }',
                'error_message' => 'CliOnlyCall',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'variadicCliOnlyInstanceParameter' => [
                'code' => '<?php' . self::REQUEST . '
                    /** @psalm-cli-only */
                    function import(): void {}

                    function run(Request ...$requests): void {
                        import();
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'cliOnlyInstanceParameterInAUnion' => [
                'code' => '<?php' . self::REQUEST . '
                    /** @psalm-cli-only */
                    function import(): void {}

                    function run(Request|string $request): void {
                        import();
                    }',
                'error_message' => 'CliOnlyCall',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'cliOnlyInstanceParameterTypedByADocblock' => [
                'code' => '<?php' . self::REQUEST . '
                    /** @psalm-cli-only */
                    function import(): void {}

                    /** @param Request $request */
                    function run($request): void {
                        import();
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'parameterOfAClassWithoutCliOnlyInstances' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    final class Request {}

                    function run(Request $request): void {
                        import();
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'staticMethodOfAClassWithCliOnlyInstances' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public static function create(): self {
                            import();
                            return new self();
                        }
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'constructorOfAClassWithCliOnlyInstancesBeforeItsGuard' => [
                'code' => '<?php
                    /** @psalm-cli-only */
                    function import(): void {}

                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                import();
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'traitMethodOfAClassWithoutCliOnlyInstances' => [
                'code' => '<?php' . self::REQUEST . '
                    /** @psalm-cli-only */
                    function import(): void {}

                    trait Logs {
                        public function log(): void {
                            import();
                        }
                    }

                    /** @psalm-cli-only-instances */
                    final class ArgsRequest {
                        use Logs;

                        public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }
                    }

                    final class Logger {
                        use Logs;
                    }',
                'error_message' => 'CliOnlyCall',
            ],
            'cliOnlyFunctionCalledWithoutACliOnlyInstance' => [
                'code' => '<?php' . self::REQUEST . '
                    /** @psalm-cli-only */
                    function import(?Request $request): void {}

                    import(null);',
                'error_message' => 'CliOnlyCall',
            ],
            'staticMethodOfACliOnlyClassWithCliOnlyInstances' => [
                'code' => '<?php
                    /**
                     * @psalm-cli-only
                     * @psalm-cli-only-instances
                     */
                    final class Request {
                        public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public static function path(): string {
                            return "";
                        }
                    }

                    echo Request::path();',
                'error_message' => 'CliOnlyCall',
            ],
            'cliOnlyInstancesOfAClassThatIsNotFinal' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    class Request {
                        final public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        final public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }
                    }',
                'error_message' => 'CliOnlyInstancesUnguarded - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:27 - The instances of Request may exist outside of the command line (@psalm-cli-only-instances): it isn\'t final',
            ],
            'cliOnlyInstancesWithoutAConstructor' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }
                    }',
                'error_message' => 'it doesn\'t declare a constructor',
            ],
            'cliOnlyInstancesWithAnInheritedConstructor' => [
                'code' => '<?php
                    abstract class Base {
                        public function __construct() {}
                    }

                    /** @psalm-cli-only-instances */
                    final class Request extends Base {
                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }
                    }',
                'error_message' => 'it doesn\'t declare a constructor',
            ],
            'cliOnlyInstancesWithAnEmptyConstructor' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __construct() {}

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }
                    }',
                'error_message' => 'CliOnlyInstancesUnguarded - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:41 - The method __construct() of a class with @psalm-cli-only-instances has to start with a check that it runs from the command line, like if (PHP_SAPI !== \'cli\') { throw ...; }, before using $this: it is empty',
            ],
            'cliOnlyInstancesWithAConstructorNotStartingWithTheGuard' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    final class Request {
                        private string $path;

                        public function __construct() {
                            $this->path = "";
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }
                    }',
                'error_message' => 'its first statement doesn\'t check that PHP_SAPI',
            ],
            'cliOnlyInstancesWithAConstructorReturningBeforeTheGuard' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __construct(bool $internal) {
                            if ($internal) {
                                return;
                            }

                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }
                    }',
                'error_message' => 'its first statement doesn\'t check that PHP_SAPI',
            ],
            'cliOnlyInstancesWithAGuardCheckingAnotherSapi' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __construct() {
                            if (PHP_SAPI !== "cli-server") {
                                throw new LogicException("Run it in the builtin web server");
                            }
                        }

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }
                    }',
                'error_message' => 'its first statement doesn\'t check that PHP_SAPI',
            ],
            'cliOnlyInstancesWithAGuardUsingThis' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                $this->log();
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function log(): void {}
                    }',
                'error_message' => 'its first statement uses $this before the check',
            ],
            'cliOnlyInstancesWithAGuardCallingAMethodThroughSelf' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                self::log();
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function log(): void {}
                    }',
                'error_message' => 'its first statement uses $this before the check',
            ],
            'cliOnlyInstancesWithoutWakeup' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }
                    }',
                'error_message' => 'it doesn\'t declare __wakeup(), which unserialize() calls instead of the constructor',
            ],
            'cliOnlyInstancesWithAnUnguardedWakeup' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __wakeup(): void {}
                    }',
                'error_message' => 'The method __wakeup() of a class with @psalm-cli-only-instances',
            ],
            'cliOnlyInstancesWithAnUnguardedUnserialize' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __serialize(): array {
                            return [];
                        }

                        public function __unserialize(array $data): void {}
                    }',
                'error_message' => 'The method __unserialize() of a class with @psalm-cli-only-instances',
            ],
            'cliOnlyInstancesWithAnInheritedUnserialize' => [
                'code' => '<?php
                    abstract class Base {
                        public function __serialize(): array {
                            return [];
                        }

                        public function __unserialize(array $data): void {}
                    }

                    /** @psalm-cli-only-instances */
                    final class Request extends Base {
                        public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }
                    }',
                'error_message' => 'it doesn\'t declare __unserialize(), which unserialize() calls instead of the constructor',
            ],
            'cliOnlyInstancesOfASerializableClass' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    final class Request implements Serializable {
                        public function __construct() {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __serialize(): array {
                            return [];
                        }

                        public function __unserialize(array $data): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function serialize(): string {
                            return "";
                        }

                        public function unserialize(string $data): void {}
                    }',
                'error_message' => 'it implements Serializable',
            ],
            'cliOnlyInstancesWithAHookedPromotedProperty' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    final class Request {
                        public function __construct(
                            public string $path {
                                set => strtolower($value);
                            },
                        ) {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }

                        public function __wakeup(): void {
                            if (PHP_SAPI !== "cli") {
                                throw new LogicException("Run it from the command line");
                            }
                        }
                    }',
                'error_message' => 'the hooks of its promoted property $path run before the check',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'cliOnlyInstancesOnAnInterface' => [
                'code' => '<?php
                    /** @psalm-cli-only-instances */
                    interface Request {}',
                'error_message' => 'InvalidDocblock',
            ],
        ];
    }
}
