<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The stubs of stubs/libraries for amphp/http-client, amphp/byte-stream and amphp/process, which Psalm loads because
 * the code below declares classes of the packages, as their sources do
 */
final class AmphpHttpClientLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace Psr\Http\Message {
                interface UriInterface {}
            }

            namespace Amp\ByteStream {
                interface ReadableStream {}
                final class Payload implements ReadableStream {}
            }

            namespace Amp\Http {
                abstract class HttpMessage {}
                abstract class HttpRequest extends HttpMessage {}
                abstract class HttpResponse extends HttpMessage {}
            }

            namespace Amp\Http\Client {
                interface HttpContent {}
                final class Request extends \Amp\Http\HttpRequest {}
                final class Form implements HttpContent {}
                final class Response extends \Amp\Http\HttpResponse {}
            }

            namespace Amp\Process {
                final class Process {}
            }';
    }

    /**
     * @return array<string, array{string, list<string>}>
     * @psalm-pure
     */
    #[Override]
    public static function providerTaints(): array
    {
        return [
            'requestUri' => [
                '<?php
                    $url = (string) $_GET["url"];
                    new Amp\Http\Client\Request($url);
                    (new Amp\Http\Client\Request("https://example.com"))->setUri($url);
                    new Amp\Http\Client\Request("https://example.com", "POST", $url);',
                [
                    'TaintedSSRF on line 3',
                    'TaintedSSRF on line 4',
                ],
            ],
            'formFile' => [
                '<?php
                    $path = (string) $_GET["path"];
                    (new Amp\Http\Client\Form())->addFile("file", $path);
                    (new Amp\Http\Client\Form())->addFile($path, "/tmp/upload");',
                [
                    'TaintedFile on line 3',
                ],
            ],
            'processCommand' => [
                '<?php
                    $command = (string) $_GET["command"];
                    Amp\Process\Process::start($command);
                    Amp\Process\Process::start(["ls", "-l"]);',
                [
                    'TaintedShell on line 3',
                ],
            ],
            'responseSources' => [
                '<?php
                    function show(Amp\Http\Client\Response $response): void {
                        echo $response->getHeader("location");
                        echo $response->getBody()->buffer();
                        $body = $response->getBody();
                        echo (string) $body->read();
                        echo (string) $body;
                    }',
                [
                    'TaintedHtml on line 3',
                    'TaintedHtml on line 4',
                    'TaintedHtml on line 6',
                    'TaintedHtml on line 7',
                    'TaintedTextWithQuotes on line 3',
                    'TaintedTextWithQuotes on line 4',
                    'TaintedTextWithQuotes on line 6',
                    'TaintedTextWithQuotes on line 7',
                ],
            ],
            'payloadTheCodeBuildsIsNoSource' => [
                '<?php
                    function show(Amp\ByteStream\Payload $payload): void {
                        echo $payload->buffer();
                    }',
                [],
            ],
        ];
    }
}
