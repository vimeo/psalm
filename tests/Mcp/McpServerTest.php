<?php

declare(strict_types=1);

namespace Psalm\Tests\Mcp;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Mcp\McpError;
use Psalm\Internal\Mcp\McpServer;
use Psalm\Internal\Mcp\ToolError;
use Psalm\Internal\VersionUtils;

use function define;
use function defined;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class McpServerTest extends TestCase
{
    private McpServer $server;

    #[Override]
    protected function setUp(): void
    {
        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', VersionUtils::getPsalmVersion());
        }

        $this->server = new McpServer(null, 'No psalm.xml was found.');
    }

    /**
     * @return iterable<string, array{mixed, string}>
     * @psalm-pure
     */
    public static function providerProtocolVersions(): iterable
    {
        yield 'a version this server speaks' => ['2025-03-26', '2025-03-26'];
        yield 'the first version' => ['2024-11-05', '2024-11-05'];
        yield 'an unknown version' => ['2099-01-01', McpServer::PROTOCOL_VERSIONS[0]];
        yield 'no version' => [null, McpServer::PROTOCOL_VERSIONS[0]];
    }

    #[DataProvider('providerProtocolVersions')]
    public function testInitializeAnswersWithTheVersionTheClientAsksForIfPossible(
        mixed $requested,
        string $expected,
    ): void {
        $response = $this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => [
            'protocolVersion' => $requested,
            'capabilities' => [],
        ]]);

        $this->assertSame($expected, self::at($response, 'result', 'protocolVersion'));
        $this->assertSame(['tools' => ['listChanged' => false]], self::at($response, 'result', 'capabilities'));
        $this->assertStringContainsString('psalm_check', self::string($response, 'result', 'instructions'));
    }

    public function testNotificationsAndResponsesGetNoAnswer(): void
    {
        $this->assertNull($this->server->handleLine('{"jsonrpc":"2.0","method":"notifications/initialized"}'));
        $this->assertNull(
            $this->server->handleLine('{"jsonrpc":"2.0","method":"notifications/cancelled","params":{}}'),
        );
        $this->assertNull($this->server->handleLine('{"jsonrpc":"2.0","id":5,"result":{}}'));
        $this->assertNull($this->server->handleLine("  \n"));
    }

    public function testPingAnswersWithAnEmptyObject(): void
    {
        $this->assertSame(
            '{"jsonrpc":"2.0","id":"a","result":{}}',
            $this->server->handleLine('{"jsonrpc":"2.0","id":"a","method":"ping"}'),
        );
    }

    public function testErrors(): void
    {
        $this->assertSame(McpError::PARSE_ERROR, self::at($this->sendLine('{not json'), 'error', 'code'));
        $this->assertSame(
            McpError::INVALID_REQUEST,
            self::at($this->send(['jsonrpc' => '2.0', 'id' => 1]), 'error', 'code'),
        );
        $this->assertSame(
            McpError::INVALID_REQUEST,
            self::at($this->send(['jsonrpc' => '2.0', 'id' => [], 'method' => 'ping']), 'error', 'code'),
        );
        $this->assertSame(
            McpError::METHOD_NOT_FOUND,
            self::at($this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'foo']), 'error', 'code'),
        );
        $this->assertSame(McpError::INVALID_PARAMS, self::at($this->send([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => ['name' => 'psalm_unknown'],
        ]), 'error', 'code'));
    }

    public function testABatchIsAnsweredWithTheResponsesOfItsRequests(): void
    {
        $responses = $this->sendLine(json_encode([
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'],
            ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
            ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'ping'],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame([1, 2], [self::at($responses, 0, 'id'), self::at($responses, 1, 'id')]);
        $this->assertCount(2, $responses);
    }

    public function testEveryToolHasAnObjectSchema(): void
    {
        $tools = self::at($this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']), 'result', 'tools');
        $this->assertIsArray($tools);

        $names = [];
        /** @var mixed $tool */
        foreach ($tools as $tool) {
            $name = self::string($tool, 'name');
            $names[] = $name;
            $this->assertSame('object', self::at($tool, 'inputSchema', 'type'), $name);
            $this->assertNotEmpty(self::at($tool, 'inputSchema', 'properties'), $name);
            $this->assertNotEmpty(self::string($tool, 'description'), $name);
            $this->assertTrue(self::at($tool, 'annotations', 'readOnlyHint'), $name);
        }

        $this->assertSame(['psalm_check', 'psalm_inspect', 'psalm_references', 'psalm_explain'], $names);
    }

    public function testTheToolsThatNeedTheProjectSayWhyItIsUnavailable(): void
    {
        $result = $this->callTool('psalm_inspect', ['file' => 'a.php', 'line' => 1]);

        $this->assertTrue(self::at($result, 'isError'));
        $this->assertSame([['type' => 'text', 'text' => 'No psalm.xml was found.']], self::at($result, 'content'));
    }

    public function testAnInvalidArgumentIsAToolError(): void
    {
        $result = $this->callTool('psalm_explain', ['issue' => 5]);

        $this->assertTrue(self::at($result, 'isError'));
        $this->assertSame('`issue` must be a string.', self::at($result, 'content', 0, 'text'));
    }

    /**
     * @return iterable<string, array{string}>
     * @psalm-pure
     */
    public static function providerIssues(): iterable
    {
        yield 'type' => ['PossiblyNullArgument'];
        yield 'shortcode' => ['78'];
        yield 'padded shortcode' => ['078'];
        yield 'link' => ['https://psalm.dev/078'];
    }

    #[DataProvider('providerIssues')]
    public function testExplainPrintsTheDocumentationOfAnIssue(string $issue): void
    {
        $this->assertStringStartsWith('# PossiblyNullArgument', McpServer::explain($issue));
    }

    public function testExplainRejectsWhatIsNotAnIssueType(): void
    {
        $this->expectException(ToolError::class);

        McpServer::explain('../../README');
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<array-key, mixed>
     */
    private function callTool(string $name, array $arguments): array
    {
        $result = self::at($this->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => [
            'name' => $name,
            'arguments' => $arguments,
        ]]), 'result');
        self::assertIsArray($result);

        return $result;
    }

    /**
     * @param array<string, mixed> $message
     * @return array<array-key, mixed>
     */
    private function send(array $message): array
    {
        return $this->sendLine(json_encode($message, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<array-key, mixed>
     */
    private function sendLine(string $line): array
    {
        $response = json_decode((string) $this->server->handleLine($line), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($response);

        return $response;
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
