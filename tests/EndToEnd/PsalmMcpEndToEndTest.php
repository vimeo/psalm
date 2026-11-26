<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use Override;
use RuntimeException;

use function dirname;
use function fclose;
use function fgets;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function fwrite;
use function getenv;
use function is_resource;
use function json_decode;
use function json_encode;
use function mkdir;
use function proc_close;
use function proc_open;
use function proc_terminate;
use function rmdir;
use function str_replace;
use function stream_select;
use function touch;
use function uniqid;
use function unlink;
use function var_export;

use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * Runs psalm-mcp in a copy of the DummyProjectWithErrors fixture and talks to it as an MCP client
 */
final class PsalmMcpEndToEndTest extends PsalmEndToEndTestCase
{
    private const GREETER = <<<'PHP'
    <?php
    namespace Acme\SampleProject;

    final class Greeter
    {
        public function name(?User $user): string
        {
            return $user->getName();
        }
    }

    final class User
    {
        public function getName(): string
        {
            return 'a';
        }
    }

    PHP;

    /** @var resource|null */
    private $process = null;

    /** @var resource|closed-resource|null */
    private $stdin = null;

    /** @var array{1?: resource} */
    private array $pipes = [];

    private string $stderr = '';

    private int $id = 0;

    #[Override]
    public function tearDown(): void
    {
        if (is_resource($this->stdin)) {
            fclose($this->stdin);
        }

        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
        }

        $this->process = null;
        $this->stdin = null;
        @unlink($this->stderr);

        parent::tearDown();
    }

    public function testTheToolsAreListed(): void
    {
        $this->initPsalm();
        $this->startServer();

        $result = $this->request('initialize', [
            'protocolVersion' => '2025-06-18',
            'capabilities' => [],
            'clientInfo' => ['name' => 'test', 'version' => '1'],
        ]);
        $this->assertSame('2025-06-18', self::at($result, 'protocolVersion'));
        $this->assertSame('psalm', self::at($result, 'serverInfo', 'name'));

        $this->notify('notifications/initialized');

        $tools = $this->request('tools/list');
        $this->assertSame(
            ['psalm_check', 'psalm_inspect', 'psalm_references', 'psalm_explain'],
            [
                self::at($tools, 'tools', 0, 'name'),
                self::at($tools, 'tools', 1, 'name'),
                self::at($tools, 'tools', 2, 'name'),
                self::at($tools, 'tools', 3, 'name'),
            ],
        );
    }

    public function testAFileIsCheckedAgainAfterItChangesOnDisk(): void
    {
        $this->initPsalm();
        $this->startServer();

        $output = $this->callTool('psalm_check', ['paths' => ['src/FileWithErrors.php']]);
        $this->assertStringContainsString('InvalidReturnType - src/FileWithErrors.php:5:', $output);
        $this->assertStringContainsString('InvalidReturnStatement', $output);

        $file = self::$tmpDir . '/src/FileWithErrors.php';
        $contents = (string) file_get_contents($file);
        file_put_contents($file, str_replace("return 'bar';", 'return 12345;', $contents));

        $output = $this->callTool('psalm_check', ['paths' => ['src/FileWithErrors.php']]);
        $this->assertStringContainsString('No issues found in 1 file.', $output);

        // the same size and modification time, so that only the contents tell that the file changed
        $mtime = (int) filemtime($file);
        file_put_contents($file, str_replace("return 'bar';", "return 'baz';", $contents));
        touch($file, $mtime);

        $output = $this->callTool('psalm_check', ['paths' => ['src/FileWithErrors.php']]);
        $this->assertStringContainsString('InvalidReturnType - src/FileWithErrors.php:5:', $output);
    }

    public function testAFileAddedAfterTheStartIsChecked(): void
    {
        $this->initPsalm();
        $this->startServer();

        $this->callTool('psalm_check', ['paths' => ['src/FileWithErrors.php']]);

        file_put_contents(self::$tmpDir . '/src/Greeter.php', self::GREETER);

        $output = $this->callTool('psalm_check', ['paths' => ['src']]);
        $this->assertStringContainsString('PossiblyNullReference - src/Greeter.php:8:23', $output);
        $this->assertStringContainsString('InvalidReturnType - src/FileWithErrors.php', $output);
    }

    public function testTheTypeAndTheSymbolAtAPositionAreShown(): void
    {
        file_put_contents(self::$tmpDir . '/src/Greeter.php', self::GREETER);
        $this->initPsalm();
        $this->startServer();

        $output = $this->callTool('psalm_inspect', ['file' => 'src/Greeter.php', 'line' => 8, 'text' => 'getName']);
        $this->assertStringContainsString('`getName()` at 8:23' . "\n" . '  type: string', $output);
        $this->assertStringContainsString(
            'symbol: Acme\SampleProject\User::getName, declared at src/Greeter.php:14:21',
            $output,
        );

        $output = $this->callTool('psalm_inspect', ['file' => 'src/Greeter.php', 'line' => 8, 'column' => 16]);
        $this->assertStringContainsString('`$user` at 8:16' . "\n" . '  type: Acme\SampleProject\User|null', $output);

        // a declaration
        $output = $this->callTool('psalm_inspect', ['file' => 'src/Greeter.php', 'line' => 14, 'text' => 'getName']);
        $this->assertStringContainsString(
            "`getName` at 14:21\n  symbol: Acme\\SampleProject\\User::getName, declared at src/Greeter.php:14:21",
            $output,
        );
    }

    public function testTheReferencesToASymbolAreListed(): void
    {
        file_put_contents(self::$tmpDir . '/src/Greeter.php', self::GREETER);
        $this->initPsalm();
        $this->startServer();

        $output = $this->callTool('psalm_references', ['symbol' => 'Acme\SampleProject\User::getName']);
        $this->assertStringContainsString('1 reference to Acme\SampleProject\User::getName:', $output);
        $this->assertStringContainsString('src/Greeter.php:8:23 return $user->getName();', $output);

        file_put_contents(
            self::$tmpDir . '/src/Greeter.php',
            str_replace('return $user->getName();', 'return "";', self::GREETER),
        );

        $output = $this->callTool('psalm_references', ['file' => 'src/Greeter.php', 'line' => 14, 'text' => 'getName']);
        $this->assertStringContainsString('No references to Acme\SampleProject\User::getName found.', $output);
    }

    public function testTheWholeProjectIsCheckedWithPsalm(): void
    {
        $this->initPsalm();
        $this->startServer();

        $output = $this->callTool('psalm_check', []);
        $this->assertStringContainsString('InvalidReturnType', $output);
        $this->assertStringContainsString('2 errors found', $output);
        $this->assertStringContainsString('Exit code 2: issues found.', $output);
    }

    public function testAPingIsAnsweredWhileTheWholeProjectIsChecked(): void
    {
        $this->initPsalm();
        $this->startServer();

        $this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => [
            'name' => 'psalm_check',
            'arguments' => (object) [],
        ]]);
        $this->send(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'ping']);

        $this->assertSame(['jsonrpc' => '2.0', 'id' => 2, 'result' => []], $this->readResponse());

        $response = $this->readResponse();
        $this->assertSame(1, self::at($response, 'id'));
        $this->assertStringContainsString('Exit code 2: issues found.', self::string($response, 'result', 'content', 0, 'text'));
    }

    public function testACancelledCheckIsStoppedAndGetsNoResponse(): void
    {
        $this->initPsalm();
        $this->startServer();

        $this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => [
            'name' => 'psalm_check',
            'arguments' => (object) [],
        ]]);
        $this->send(['jsonrpc' => '2.0', 'method' => 'notifications/cancelled', 'params' => ['requestId' => 1]]);
        // it waits for the first check to stop
        $this->send(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => [
            'name' => 'psalm_check',
            'arguments' => (object) [],
        ]]);
        $this->send(['jsonrpc' => '2.0', 'id' => 3, 'method' => 'ping']);

        $this->assertSame(3, self::at($this->readResponse(), 'id'));
        $this->assertSame(2, self::at($this->readResponse(), 'id'));
    }

    public function testClosingTheInputStopsTheCheckThatRuns(): void
    {
        $this->initPsalm();
        $this->startServer();

        $this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => [
            'name' => 'psalm_check',
            'arguments' => (object) [],
        ]]);
        $this->send(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'ping']);
        $this->assertSame(2, self::at($this->readResponse(), 'id'));

        if (is_resource($this->stdin)) {
            fclose($this->stdin);
        }

        $this->assertNull($this->readLine(), 'the check was answered');
    }

    public function testWithoutAConfigTheToolsSayHowToCreateOne(): void
    {
        $this->startServer();

        $result = $this->request('tools/call', ['name' => 'psalm_check', 'arguments' => (object) []]);
        $this->assertTrue(self::at($result, 'isError'));
        $this->assertStringContainsString('vendor/bin/psalm --init', self::string($result, 'content', 0, 'text'));

        $result = $this->request('tools/call', ['name' => 'psalm_explain', 'arguments' => ['issue' => '11']]);
        $this->assertFalse(self::at($result, 'isError'));
        $this->assertStringStartsWith('# InvalidReturnType', self::string($result, 'content', 0, 'text'));
    }

    public function testTheLauncherOfTheSkillSaysWhenPsalmIsNotInstalled(): void
    {
        $this->initPsalm();
        // and no global Composer packages either
        $nowhere = self::$tmpDir . '/nowhere';
        $this->startServer([__DIR__ . '/../../skills/psalm/scripts/server.php', 'mcp', self::$tmpDir], [
            'PATH' => '',
            'HOME' => $nowhere,
            'USERPROFILE' => $nowhere,
            'APPDATA' => $nowhere,
            'COMPOSER_HOME' => $nowhere,
        ]);

        $this->request('initialize', ['protocolVersion' => '2025-06-18', 'capabilities' => []]);
        $result = $this->request('tools/call', ['name' => 'psalm_check', 'arguments' => (object) []]);

        $this->assertTrue(self::at($result, 'isError'));
        $this->assertStringContainsString(
            'Install it with `composer require --dev vimeo/psalm`',
            self::string($result, 'content', 0, 'text'),
        );
    }

    public function testTheLauncherOfTheSkillStartsThePsalmOfTheProject(): void
    {
        $this->initPsalm();
        $this->installPsalmPackage();

        // from a subdirectory of the repository
        @mkdir(self::$tmpDir . '/.git');

        try {
            $this->startServer([__DIR__ . '/../../skills/psalm/scripts/server.php', 'mcp', self::$tmpDir . '/src']);

            $output = $this->callTool('psalm_check', ['paths' => ['src/FileWithErrors.php']]);
            $this->assertStringContainsString('InvalidReturnType - src/FileWithErrors.php:5:', $output);
        } finally {
            $this->removePsalmPackage();
            @rmdir(self::$tmpDir . '/.git');
        }
    }

    public function testTheLauncherOfTheSkillDoesNotRunThePsalmOfADirectoryAboveTheRepository(): void
    {
        $this->initPsalm();
        $this->installPsalmPackage();

        $repository = self::$tmpDir . '/src/repository';
        @mkdir($repository . '/.git', 0777, true);

        try {
            $this->startServer([__DIR__ . '/../../skills/psalm/scripts/server.php', 'mcp', $repository], [
                'PATH' => '',
            ]);

            $result = $this->request('tools/call', ['name' => 'psalm_check', 'arguments' => (object) []]);
            $this->assertTrue(self::at($result, 'isError'));
            $this->assertStringContainsString('Psalm is not installed in', self::string($result, 'content', 0, 'text'));
        } finally {
            $this->removePsalmPackage();
        }
    }

    /**
     * A vimeo/psalm package in the project whose psalm-mcp runs this checkout's, without a symlink: the fixture is
     * removed recursively, which would follow one into this checkout
     */
    private function installPsalmPackage(): void
    {
        $package = self::$tmpDir . '/vendor/vimeo/psalm';
        mkdir($package, 0777, true);
        file_put_contents($package . '/psalm', '');
        file_put_contents(
            $package . '/psalm-mcp',
            '<?php require ' . var_export(dirname(__DIR__, 2) . '/src/Psalm/Internal/Cli/Mcp.php', true) . ';'
                . ' \\Psalm\\Internal\\Cli\\Mcp::run($argv);',
        );
    }

    private function removePsalmPackage(): void
    {
        $package = self::$tmpDir . '/vendor/vimeo/psalm';
        unlink($package . '/psalm');
        unlink($package . '/psalm-mcp');
        rmdir($package);
        rmdir(dirname($package));
    }

    private function initPsalm(): void
    {
        $this->runPsalmInit(1);
        $config = (string) file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(self::$tmpDir . '/psalm.xml', str_replace('<psalm', '<psalm runTaintAnalysis="false"', $config));
    }

    /**
     * @param list<string> $command the PHP script and its arguments
     * @param array<string, string> $env
     */
    private function startServer(array $command = [__DIR__ . '/../../psalm-mcp'], array $env = []): void
    {
        // one file per server: on Windows, a file deleted while a process still has it open can't be created
        // again until that process exits, and the wrapper amphp starts the whole-project run with can outlive the server
        $this->stderr = self::$tmpDir . '/mcp-stderr-' . uniqid() . '.txt';

        // a file, so that a full pipe can't block the server
        $process = proc_open(
            [PHP_BINARY, ...$command],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['file', $this->stderr, 'w']],
            $pipes,
            self::$tmpDir,
            [...self::getServerEnvironment(), ...$env],
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Could not start psalm-mcp');
        }

        if (!isset($pipes[0], $pipes[1])) {
            throw new RuntimeException('psalm-mcp has no pipes');
        }

        $this->process = $process;
        $this->stdin = $pipes[0];
        $this->pipes = [1 => $pipes[1]];
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function callTool(string $name, array $arguments): string
    {
        $result = $this->request('tools/call', ['name' => $name, 'arguments' => (object) $arguments]);
        $text = self::string($result, 'content', 0, 'text');
        $this->assertFalse(self::at($result, 'isError'), $text);

        // the separator in paths depends on how Psalm found the file, not only on the system
        return str_replace(['src\\', 'vendor\\'], ['src/', 'vendor/'], $text);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<array-key, mixed>
     */
    private function request(string $method, array $params = []): array
    {
        $id = ++$this->id;
        $this->send(['jsonrpc' => '2.0', 'id' => $id, 'method' => $method, 'params' => (object) $params]);

        $response = $this->readResponse();
        $this->assertSame($id, self::at($response, 'id'));
        /** @var mixed $result */
        $result = self::at($response, 'result');
        $this->assertIsArray($result);

        return $result;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function readResponse(): array
    {
        $line = $this->readLine();
        if ($line === null) {
            throw new RuntimeException('psalm-mcp stopped: ' . (string) file_get_contents($this->stderr));
        }

        /** @var mixed $response */
        $response = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($response, $line);

        return $response;
    }

    /**
     * The next line of the output, null once the server closed it
     */
    private function readLine(): ?string
    {
        if (!isset($this->pipes[1])) {
            throw new RuntimeException('psalm-mcp is not running');
        }

        $read = [$this->pipes[1]];
        $write = $except = null;
        if (stream_select($read, $write, $except, self::PROCESS_TIMEOUT) !== 1) {
            throw new RuntimeException('psalm-mcp did not answer: ' . (string) file_get_contents($this->stderr));
        }

        $line = fgets($this->pipes[1]);

        return $line === false ? null : $line;
    }

    private function notify(string $method): void
    {
        $this->send(['jsonrpc' => '2.0', 'method' => $method]);
    }

    /**
     * @param array<string, mixed> $message
     */
    private function send(array $message): void
    {
        if (!is_resource($this->stdin)) {
            throw new RuntimeException('psalm-mcp is not running');
        }

        fwrite($this->stdin, json_encode($message, JSON_THROW_ON_ERROR) . "\n");
    }

    /**
     * The environment of this process, without the variables that tell Psalm an agent runs it
     *
     * @return array<string, string>
     */
    private static function getServerEnvironment(): array
    {
        $env = getenv();
        foreach (self::agentEnvVarsToUnset() as $name => $_) {
            unset($env[$name]);
        }

        return $env;
    }

    /**
     * The value under the keys, failing the test if one is missing
     */
    private static function at(mixed $value, string|int ...$keys): mixed
    {
        foreach ($keys as $key) {
            self::assertIsArray($value);
            self::assertArrayHasKey($key, $value);
            /** @var mixed $value */
            $value = $value[$key];
        }

        return $value;
    }

    private static function string(mixed $value, string|int ...$keys): string
    {
        /** @var mixed $value */
        $value = self::at($value, ...$keys);
        self::assertIsString($value);

        return $value;
    }
}
