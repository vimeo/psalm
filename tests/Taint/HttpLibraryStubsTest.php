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
                interface ResponseInterface extends MessageInterface {}
                interface StreamInterface {}
                interface UriInterface {}
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
            // a message the code is given holds only what it was given, each its own
            'messageNotFromAClient' => [
                '<?php
                    function show(Psr\Http\Message\ResponseInterface $response): void {
                        echo $response->getBody()->getContents();
                    }',
                [],
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
