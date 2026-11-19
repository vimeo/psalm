<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The stubs of stubs/libraries for guzzlehttp/guzzle, guzzlehttp/psr7, guzzlehttp/promises, psr/http-message and
 * psr/http-client, which Psalm loads because the code below declares classes of these packages, as their sources do.
 */
final class HttpLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace Psr\Http\Message {
                interface MessageInterface {}
                interface RequestInterface extends MessageInterface {}
                interface ServerRequestInterface extends RequestInterface {}
                interface ResponseInterface extends MessageInterface {}
                interface StreamInterface {}
                interface UriInterface {}
                interface UploadedFileInterface {}
            }

            namespace Laminas\Diactoros {
                abstract class ServerRequest implements \Psr\Http\Message\ServerRequestInterface {
                    public function getQueryParams(): array {
                        return [];
                    }
                }
            }

            namespace Psr\Http\Client {
                interface ClientInterface {}
            }

            namespace GuzzleHttp\Promise {
                interface PromiseInterface {}
            }

            namespace GuzzleHttp\Psr7 {
                trait MessageTrait {}
                class Request implements \Psr\Http\Message\RequestInterface {
                    use MessageTrait;
                }
                class Uri implements \Psr\Http\Message\UriInterface, \JsonSerializable {
                    public function jsonSerialize(): string {
                        return "";
                    }
                }
            }

            namespace GuzzleHttp\Cookie {
                class CookieJar {}
                class FileCookieJar extends CookieJar {}
            }

            namespace GuzzleHttp {
                interface ClientInterface {}
                trait ClientTrait {}
                class Client implements ClientInterface, \Psr\Http\Client\ClientInterface {
                    use ClientTrait;
                }
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
            'clientUri' => [
                '<?php
                    $url = (string) $_GET["url"];
                    (new GuzzleHttp\Client())->get($url);
                    (new GuzzleHttp\Client())->request("POST", $url);
                    (new GuzzleHttp\Client())->postAsync($url);',
                [
                    'TaintedSSRF on line 3',
                    'TaintedSSRF on line 4',
                    'TaintedSSRF on line 5',
                ],
            ],
            'clientResponse' => [
                '<?php
                    $response = (new GuzzleHttp\Client())->get("https://example.com");
                    echo $response->getBody()->getContents();
                    echo $response->getHeaderLine("Location");',
                [
                    'TaintedHtml on line 3',
                    'TaintedHtml on line 4',
                    'TaintedTextWithQuotes on line 3',
                    'TaintedTextWithQuotes on line 4',
                ],
            ],
            'promise' => [
                '<?php
                    echo (string) (new GuzzleHttp\Client())->getAsync("https://example.com")->wait();',
                [
                    'TaintedHtml on line 2',
                    'TaintedTextWithQuotes on line 2',
                ],
            ],
            'psr18Response' => [
                '<?php
                    use Psr\Http\Client\ClientInterface;
                    use Psr\Http\Message\RequestInterface;

                    function fetch(ClientInterface $client, RequestInterface $request): void {
                        echo $client->sendRequest($request)->getBody()->getContents();
                    }',
                [
                    'TaintedHtml on line 6',
                    'TaintedTextWithQuotes on line 6',
                ],
            ],
            'messageNotFromAClient' => [
                '<?php
                    function show(Psr\Http\Message\ResponseInterface $response): void {
                        echo $response->getBody()->getContents();
                        echo $response->getReasonPhrase();
                    }',
                [
                    'TaintedHtml on line 3',
                    'TaintedHtml on line 4',
                    'TaintedTextWithQuotes on line 3',
                    'TaintedTextWithQuotes on line 4',
                ],
            ],
            'serverRequest' => [
                '<?php
                    use Psr\Http\Message\ServerRequestInterface;
                    use Psr\Http\Message\UploadedFileInterface;

                    function handle(ServerRequestInterface $request): void {
                        echo (string) ($request->getQueryParams()["q"] ?? "");
                        echo (string) $request->getAttribute("id");
                        echo $request->getUri()->getPath();
                        echo (string) $request->getBody();
                        shell_exec((string) ($request->getCookieParams()["c"] ?? ""));
                        $parsed = (array) $request->getParsedBody();
                        shell_exec((string) ($parsed["cmd"] ?? ""));
                        $file = $request->getUploadedFiles()["avatar"] ?? null;
                        if ($file instanceof UploadedFileInterface) {
                            file_put_contents("/uploads/" . (string) $file->getClientFilename(), "");
                        }
                    }',
                [
                    'TaintedFile on line 15',
                    'TaintedHtml on line 6',
                    'TaintedHtml on line 7',
                    'TaintedHtml on line 8',
                    'TaintedHtml on line 9',
                    'TaintedShell on line 10',
                    'TaintedShell on line 12',
                    'TaintedTextWithQuotes on line 6',
                    'TaintedTextWithQuotes on line 7',
                    'TaintedTextWithQuotes on line 8',
                    'TaintedTextWithQuotes on line 9',
                ],
            ],
            'serverParams' => [
                '<?php
                    function handle(Psr\Http\Message\ServerRequestInterface $request, string $key): void {
                        echo (string) ($request->getServerParams()["REQUEST_URI"] ?? "");
                        echo (string) ($request->getServerParams()["HTTP_REFERER"] ?? "");
                        echo (string) ($request->getServerParams()["REDIRECT_QUERY_STRING"] ?? "");
                        echo (string) ($request->getServerParams()["DOCUMENT_ROOT"] ?? "");
                        echo (string) ($request->getServerParams()["REMOTE_ADDR"] ?? "");
                        echo (string) ($request->getServerParams()[$key] ?? "");
                    }',
                [
                    'TaintedHtml on line 3',
                    'TaintedHtml on line 4',
                    'TaintedHtml on line 5',
                    'TaintedHtml on line 8',
                    'TaintedTextWithQuotes on line 3',
                    'TaintedTextWithQuotes on line 4',
                    'TaintedTextWithQuotes on line 5',
                    'TaintedTextWithQuotes on line 8',
                ],
            ],
            'uploadedFileStreamMetadata' => [
                '<?php
                    function handle(Psr\Http\Message\UploadedFileInterface $file): void {
                        file_get_contents((string) $file->getStream()->getMetadata("uri"));
                        echo $file->getStream()->getContents();
                    }',
                [
                    'TaintedHtml on line 4',
                    'TaintedTextWithQuotes on line 4',
                ],
            ],
            'serverRequestImplementation' => [
                '<?php
                    function handle(Laminas\Diactoros\ServerRequest $request): void {
                        echo (string) ($request->getQueryParams()["q"] ?? "");
                        echo $request->getHeaderLine("Referer");
                        echo (string) ($request->getServerParams()["QUERY_STRING"] ?? "");
                        echo (string) ($request->getServerParams()["SERVER_NAME"] ?? "");
                    }',
                [
                    'TaintedHtml on line 3',
                    'TaintedHtml on line 4',
                    'TaintedHtml on line 5',
                    'TaintedTextWithQuotes on line 3',
                    'TaintedTextWithQuotes on line 4',
                    'TaintedTextWithQuotes on line 5',
                ],
            ],
            'requestUri' => [
                '<?php
                    new GuzzleHttp\Psr7\Request("GET", new GuzzleHttp\Psr7\Uri((string) $_GET["url"]));
                    new GuzzleHttp\Psr7\Request("GET", new GuzzleHttp\Psr7\Uri("https://example.com"));',
                [
                    'TaintedSSRF on line 2',
                ],
            ],
            'cookieFile' => [
                '<?php
                    new GuzzleHttp\Cookie\FileCookieJar((string) $_GET["file"]);',
                [
                    'TaintedFile on line 2',
                ],
            ],
        ];
    }
}
