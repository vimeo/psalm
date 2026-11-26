<?php

declare(strict_types=1);

namespace Psalm\Internal\Mcp;

use Amp\ByteStream\ReadableResourceStream;
use Amp\ByteStream\StreamException;
use Amp\ByteStream\WritableResourceStream;
use Amp\Cancellation;
use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\Future;
use JsonException;
use Psalm\Config\IssueHandler;
use Psalm\Internal\CliUtils;
use Revolt\EventLoop;
use Revolt\EventLoop\Driver\StreamSelectDriver;
use Throwable;
use stdClass;

use function Amp\async;
use function array_filter;
use function array_is_list;
use function array_key_exists;
use function array_map;
use function array_pop;
use function array_values;
use function class_exists;
use function constant;
use function defined;
use function dirname;
use function explode;
use function fflush;
use function fgets;
use function file_get_contents;
use function fwrite;
use function in_array;
use function is_array;
use function is_bool;
use function is_file;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;
use function preg_match;
use function str_replace;
use function trim;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PHP_OS_FAMILY;

/**
 * A Model Context Protocol server: JSON-RPC messages, one per line, on stdin and stdout. Each message is handled in
 * its own fiber, so that a ping or a cancellation is answered while Psalm checks the project.
 *
 * @see https://modelcontextprotocol.io/specification
 * @internal
 */
final class McpServer
{
    /** The protocol versions this server speaks, newest first */
    public const PROTOCOL_VERSIONS = ['2025-11-25', '2025-06-18', '2025-03-26', '2024-11-05'];

    private const INSTRUCTIONS = <<<'TXT'
    Psalm, the static analyzer of this PHP project. After editing PHP files, call psalm_check with the files you
    changed and fix what it reports; before you finish, call psalm_check without paths, which runs Psalm on the whole
    project like CI does. To see the type Psalm infers for a variable or an expression, or what a name refers to and
    where it is declared, call psalm_inspect. psalm_references lists where a class, function, method, property or
    constant is used. psalm_explain prints the documentation of an issue type, with how to fix it. Paths are relative
    to the project root. Lines and columns start at 1.
    TXT;

    /**
     * The requests in progress, by id, to cancel them
     *
     * @var array<string, DeferredCancellation>
     */
    private array $requests = [];

    public function __construct(
        private readonly ?Session $session,
        private readonly ?string $unavailable_reason,
    ) {
    }

    /**
     * Serves the client until it closes the input
     *
     * @param resource $input
     * @param resource $output
     */
    public function run($input, $output): void
    {
        // the analysis forks its workers while this loop runs, which a driver with a native handle can't do (see Pool)
        if (EventLoop::getDriver()->getHandle() !== null) {
            EventLoop::setDriver(new StreamSelectDriver());
        }

        if (PHP_OS_FAMILY === 'Windows') {
            // the event loop can't wait for a pipe on Windows, so messages are read and handled one at a time
            while (($line = fgets($input)) !== false) {
                $response = $this->handleLine($line);

                if ($response !== null) {
                    fwrite($output, $response . "\n");
                    fflush($output);
                }
            }

            return;
        }

        $input = new ReadableResourceStream($input);
        $output = new WritableResourceStream($output);

        /** @var array<int, Future<void>> $handlers */
        $handlers = [];
        $buffer = '';

        do {
            $chunk = $input->read();
            $buffer .= $chunk ?? "\n";
            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines);

            foreach ($lines as $line) {
                $handlers[] = async(function () use ($line, $output): void {
                    $response = $this->handleLine($line);

                    if ($response === null) {
                        return;
                    }

                    try {
                        $output->write($response . "\n");
                    } catch (StreamException) {
                        // the client is gone
                    }
                });
            }

            $handlers = array_filter($handlers, static fn(Future $handler): bool => !$handler->isComplete());
        } while ($chunk !== null);

        // closing the input is how a client stops the server
        foreach ($this->requests as $request) {
            $request->cancel();
        }

        Future\awaitAll($handlers);
    }

    /**
     * The response to one line of input, null if it holds only notifications
     */
    public function handleLine(string $line): ?string
    {
        $line = trim($line);

        if ($line === '') {
            return null;
        }

        try {
            /** @var mixed $message */
            $message = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return self::encode(self::error(null, McpError::PARSE_ERROR, 'Parse error'));
        }

        // batches were part of the 2025-03-26 revision
        if (is_array($message) && $message !== [] && array_is_list($message)) {
            $responses = array_values(array_filter(Future\await(array_map(
                fn(mixed $item): Future => async($this->handleMessage(...), $item),
                $message,
            ))));

            return $responses === [] ? null : self::encode($responses);
        }

        $response = $this->handleMessage($message);

        return $response === null ? null : self::encode($response);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function handleMessage(mixed $message): ?array
    {
        if (!is_array($message) || !isset($message['method']) || !is_string($message['method'])) {
            // responses to requests this server never sends need no answer
            if (is_array($message) && (array_key_exists('result', $message) || array_key_exists('error', $message))) {
                return null;
            }

            return self::error(null, McpError::INVALID_REQUEST, 'Invalid Request');
        }

        if (!array_key_exists('id', $message)) {
            if ($message['method'] === 'notifications/cancelled') {
                $this->cancel($message['params'] ?? null);
            }

            // notifications: initialized, cancelled, roots changed, … need no answer
            return null;
        }

        /** @var mixed $id */
        $id = $message['id'];

        if (!is_int($id) && !is_string($id)) {
            return self::error(null, McpError::INVALID_REQUEST, 'Invalid Request: the id must be a string or a number');
        }

        /** @var mixed $params */
        $params = $message['params'] ?? [];

        if (!is_array($params)) {
            return self::error($id, McpError::INVALID_PARAMS, 'Invalid params');
        }

        $key = self::getRequestKey($id);
        $request = new DeferredCancellation();
        $this->requests[$key] = $request;

        try {
            $result = match ($message['method']) {
                'initialize' => $this->initialize($params),
                'ping' => new stdClass(),
                'tools/list' => ['tools' => self::getToolDefinitions()],
                'tools/call' => $this->callTool($params, $request->getCancellation()),
                default => throw new McpError(McpError::METHOD_NOT_FOUND, 'Method not found: ' . $message['method']),
            };
        } catch (McpError $e) {
            return self::error($id, $e->getCode(), $e->getMessage());
        } catch (CancelledException) {
            return null;
        } finally {
            if (($this->requests[$key] ?? null) === $request) {
                unset($this->requests[$key]);
            }
        }

        // a cancelled request gets no response, also if it was too late to stop it
        return $request->isCancelled() ? null : ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function cancel(mixed $params): void
    {
        /** @var mixed $id */
        $id = is_array($params) ? $params['requestId'] ?? null : null;

        if (is_int($id) || is_string($id)) {
            ($this->requests[self::getRequestKey($id)] ?? null)?->cancel();
        }
    }

    /**
     * @psalm-pure
     */
    private static function getRequestKey(int|string $id): string
    {
        // the request 1 isn't the request "1"
        return (is_int($id) ? 'n' : 's') . $id;
    }

    /**
     * @param array<array-key, mixed> $params
     * @return array<string, mixed>
     * @psalm-pure
     */
    private function initialize(array $params): array
    {
        /** @var mixed $requested */
        $requested = $params['protocolVersion'] ?? null;

        return [
            'protocolVersion' => is_string($requested) && in_array($requested, self::PROTOCOL_VERSIONS, true)
                ? $requested
                : self::PROTOCOL_VERSIONS[0],
            'capabilities' => ['tools' => ['listChanged' => false]],
            'serverInfo' => ['name' => 'psalm', 'title' => 'Psalm', 'version' => PSALM_VERSION],
            'instructions' => str_replace("\n", ' ', self::INSTRUCTIONS),
        ];
    }

    /**
     * @param array<array-key, mixed> $params
     * @return array<string, mixed>
     */
    private function callTool(array $params, Cancellation $cancellation): array
    {
        $name = $params['name'] ?? null;
        /** @var mixed $arguments */
        $arguments = $params['arguments'] ?? [];

        if (!is_string($name)) {
            throw new McpError(McpError::INVALID_PARAMS, 'Invalid params: `name` must be a string');
        }

        if (!is_array($arguments)) {
            throw new McpError(McpError::INVALID_PARAMS, 'Invalid params: `arguments` must be an object');
        }

        if (!in_array($name, ['psalm_check', 'psalm_inspect', 'psalm_references', 'psalm_explain'], true)) {
            throw new McpError(McpError::INVALID_PARAMS, 'Unknown tool: ' . $name);
        }

        try {
            $text = $this->runTool($name, $arguments, $cancellation);
            $is_error = false;
        } catch (CancelledException $e) {
            throw $e;
        } catch (ToolError $e) {
            $text = $e->getMessage();
            $is_error = true;
        } catch (Throwable $e) {
            $text = 'Psalm crashed, this says nothing about the code: ' . $e::class . ': ' . $e->getMessage()
                . "\n" . $e->getTraceAsString();
            $is_error = true;
        }

        return [
            'content' => [['type' => 'text', 'text' => $text]],
            'isError' => $is_error,
        ];
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    private function runTool(string $name, array $arguments, Cancellation $cancellation): string
    {
        if ($name === 'psalm_explain') {
            return self::explain(self::getString($arguments, 'issue') ?? throw new ToolError('`issue` is required.'));
        }

        if ($this->session === null) {
            throw new ToolError($this->unavailable_reason ?? 'Psalm is not available.');
        }

        return match ($name) {
            'psalm_check' => $this->session->check(
                self::getPaths($arguments),
                self::getBool($arguments, 'show_info') ?? false,
                $cancellation,
            ),
            'psalm_inspect' => $this->session->inspect(
                self::getString($arguments, 'file') ?? throw new ToolError('`file` is required.'),
                self::getInt($arguments, 'line') ?? throw new ToolError('`line` is required.'),
                self::getInt($arguments, 'column'),
                self::getString($arguments, 'text'),
                $cancellation,
            ),
            default => $this->session->references(
                self::getString($arguments, 'symbol'),
                self::getString($arguments, 'file'),
                self::getInt($arguments, 'line'),
                self::getInt($arguments, 'column'),
                self::getString($arguments, 'text'),
                $cancellation,
            ),
        };
    }

    /**
     * The documentation page of an issue type, given as its name, its shortcode or its psalm.dev link
     */
    public static function explain(string $query): string
    {
        $query = trim($query);
        $type = $query;

        if (preg_match('~^(?:https?://psalm\.dev/)?0*(\d+)$~', $query, $matches) === 1 && isset($matches[1])) {
            $shortcode = (int) $matches[1];
            $type = null;

            foreach (IssueHandler::getAllIssueTypes() as $issue_type) {
                $constant = 'Psalm\\Issue\\' . $issue_type . '::SHORTCODE';
                if (class_exists('Psalm\\Issue\\' . $issue_type)
                    && defined($constant)
                    && constant($constant) === $shortcode
                ) {
                    $type = $issue_type;
                    break;
                }
            }

            if ($type === null) {
                throw new ToolError('No issue type has the shortcode ' . $shortcode . '.');
            }
        }

        $doc = null;

        // a name, so that the path stays in the issues directory
        if (preg_match('/^\w+$/', $type) === 1) {
            $doc = dirname(__DIR__, 4) . '/docs/running_psalm/issues/' . $type . '.md';
        }

        if ($doc === null || !is_file($doc)) {
            throw new ToolError('`' . $query . '` is not an issue type. Pass the type that Psalm reports, such as'
                . ' `PossiblyNullReference`, or its shortcode.');
        }

        return (string) file_get_contents($doc);
    }

    /**
     * @return list<array<string, mixed>>
     * @psalm-pure
     */
    public static function getToolDefinitions(): array
    {
        $position = [
            'file' => [
                'type' => 'string',
                'description' => 'The PHP file, relative to the project root',
            ],
            'line' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'The line, starting at 1',
            ],
            'column' => [
                'type' => 'integer',
                'minimum' => 1,
                'description' => 'The column, starting at 1 (in bytes, as Psalm reports columns)',
            ],
            'text' => [
                'type' => 'string',
                'description' => 'Code on that line to point at instead of a column, e.g. `$user` or `getName`:'
                    . ' its first occurrence on the line',
            ],
        ];

        $read_only = ['readOnlyHint' => true, 'destructiveHint' => false, 'openWorldHint' => false];

        return [
            [
                'name' => 'psalm_check',
                'title' => 'Check PHP code with Psalm',
                'description' => 'Runs Psalm and returns the issues it reports, each with its location, issue type,'
                    . ' message and code snippet. Give the files or directories you changed for a fast check'
                    . ' (incremental, in memory). Without paths it runs Psalm on the whole project, like CI does,'
                    . ' which also finds unused code and taint paths between files: do that before you finish.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'paths' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Files or directories, relative to the project root. Omit to check'
                                . ' the whole project.',
                        ],
                        'show_info' => [
                            'type' => 'boolean',
                            'description' => 'Also report the issues that the config downgrades to info',
                        ],
                    ],
                ],
                'annotations' => $read_only,
            ],
            [
                'name' => 'psalm_inspect',
                'title' => 'Inspect types and symbols',
                'description' => 'Shows what Psalm knows about the code at a position: the type it infers for each'
                    . ' variable and expression there, and for a class, function, method, property or constant,'
                    . ' its signature, docblock and where it is declared. Point at the code with `column` or `text`;'
                    . ' with neither, it lists everything on the line. Use it to find out why Psalm reports an'
                    . ' issue, instead of guessing a type.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => $position,
                    'required' => ['file', 'line'],
                ],
                'annotations' => $read_only,
            ],
            [
                'name' => 'psalm_references',
                'title' => 'Find references',
                'description' => 'Lists where a class, interface, trait, enum, function, method, property or class'
                    . ' constant is used in the project. Name it with `symbol` (fully qualified: `App\\User`,'
                    . ' `App\\User::getName`, `App\\User::$name`, `App\\User::ROLE`, `App\\format_name`), or point'
                    . ' at a use of it with `file`, `line` and `column` or `text`. The first call analyses the whole'
                    . ' project.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'symbol' => [
                            'type' => 'string',
                            'description' => 'The fully qualified name of the symbol',
                        ],
                    ] + $position,
                ],
                'annotations' => $read_only,
            ],
            [
                'name' => 'psalm_explain',
                'title' => 'Explain an issue type',
                'description' => 'Returns the documentation of a Psalm issue type: what it means, an example, and'
                    . ' how to fix it.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'issue' => [
                            'type' => 'string',
                            'description' => 'The issue type (`PossiblyNullReference`), its shortcode (`78`) or its'
                                . ' link (`https://psalm.dev/078`)',
                        ],
                    ],
                    'required' => ['issue'],
                ],
                'annotations' => $read_only,
            ],
        ];
    }

    /**
     * @param array<array-key, mixed> $arguments
     * @return list<string>
     * @psalm-pure
     */
    private static function getPaths(array $arguments): array
    {
        /** @var mixed $paths */
        $paths = $arguments['paths'] ?? [];

        if (is_string($paths)) {
            $paths = [$paths];
        }

        if (!is_array($paths)) {
            throw new ToolError('`paths` must be a list of strings.');
        }

        return array_values(array_map(
            static fn(mixed $path): string => is_string($path) && $path !== ''
                ? CliUtils::chosenByOperator($path)
                : throw new ToolError('`paths` must be a list of strings.'),
            $paths,
        ));
    }

    /**
     * The agent that started this server asks for the files and symbols of the project for its user, like the
     * arguments of `psalm` do, so its arguments are the operator's
     *
     * @param array<array-key, mixed> $arguments
     * @psalm-pure
     */
    private static function getString(array $arguments, string $name): ?string
    {
        /** @var mixed $value */
        $value = $arguments[$name] ?? null;

        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new ToolError('`' . $name . '` must be a string.');
        }

        return CliUtils::chosenByOperator($value);
    }

    /**
     * @param array<array-key, mixed> $arguments
     * @psalm-pure
     */
    private static function getInt(array $arguments, string $name): ?int
    {
        /** @var mixed $value */
        $value = $arguments[$name] ?? null;

        if ($value === null) {
            return null;
        }

        // some clients send numbers as strings
        if (is_string($value) && preg_match('/^\d+$/', $value)) {
            return (int) $value;
        }

        if (!is_int($value)) {
            throw new ToolError('`' . $name . '` must be an integer.');
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $arguments
     * @psalm-pure
     */
    private static function getBool(array $arguments, string $name): ?bool
    {
        /** @var mixed $value */
        $value = $arguments[$name] ?? null;

        if ($value === null) {
            return null;
        }

        if (!is_bool($value)) {
            throw new ToolError('`' . $name . '` must be a boolean.');
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     * @psalm-pure
     */
    private static function error(int|string|null $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }

    /**
     * @psalm-pure
     */
    private static function encode(mixed $value): string
    {
        return json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        );
    }
}
