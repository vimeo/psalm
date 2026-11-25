<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The stub of stubs/libraries for symfony/http-client-contracts, which Psalm loads because the code below declares
 * interfaces of the package, as its sources do
 */
final class SymfonyHttpClientLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace Symfony\Contracts\HttpClient {
                interface HttpClientInterface {
                    public function request(string $method, string $url, array $options = []): ResponseInterface;

                    /**
                     * @param ResponseInterface|iterable<array-key, ResponseInterface> $responses
                     */
                    public function stream(
                        ResponseInterface|iterable $responses,
                        ?float $timeout = null,
                    ): ResponseStreamInterface;
                }

                interface ResponseInterface {
                    public function getStatusCode(): int;
                    public function getHeaders(bool $throw = true): array;
                    public function getContent(bool $throw = true): string;
                    public function toArray(bool $throw = true): array;
                    public function getInfo(?string $type = null): mixed;
                }

                interface ChunkInterface {
                    public function isLast(): bool;
                    public function getInformationalStatus(): ?array;
                    public function getContent(): string;
                    public function getOffset(): int;
                }

                interface ResponseStreamInterface extends \Iterator {
                    public function key(): ResponseInterface;
                    public function current(): ChunkInterface;
                }
            }

            namespace Symfony\Component\HttpClient\Response {
                final class MockResponse implements \Symfony\Contracts\HttpClient\ResponseInterface {
                    public function __construct(private string $body = "") {}
                    public function getStatusCode(): int {
                        return 200;
                    }
                    public function getHeaders(bool $throw = true): array {
                        return [];
                    }
                    public function getContent(bool $throw = true): string {
                        return $this->body;
                    }
                    public function toArray(bool $throw = true): array {
                        return [];
                    }
                    public function getInfo(?string $type = null): mixed {
                        return null;
                    }
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
            'requestUrl' => [
                '<?php
                    use Symfony\Contracts\HttpClient\HttpClientInterface;

                    function fetch(HttpClientInterface $client): void {
                        $client->request("GET", (string) $_GET["url"]);
                        $client->request("GET", "https://example.com/" . rawurlencode((string) $_GET["id"]));
                        $client->request("GET", "https://example.com", ["query" => ["q" => (string) $_GET["q"]]]);
                    }',
                [
                    'TaintedSSRF on line 5',
                ],
            ],
            'response' => [
                '<?php
                    use Symfony\Contracts\HttpClient\HttpClientInterface;

                    function fetch(HttpClientInterface $client): void {
                        $response = $client->request("GET", "https://example.com");
                        echo $response->getContent();
                        echo (string) ($response->toArray()["name"] ?? "");
                        echo $response->getHeaders()["location"][0] ?? "";
                        echo (string) $response->getInfo("redirect_url");
                        echo $response->getStatusCode();
                    }',
                [
                    'TaintedHtml on line 6',
                    'TaintedHtml on line 7',
                    'TaintedHtml on line 8',
                    'TaintedHtml on line 9',
                    'TaintedTextWithQuotes on line 6',
                    'TaintedTextWithQuotes on line 7',
                    'TaintedTextWithQuotes on line 8',
                    'TaintedTextWithQuotes on line 9',
                ],
            ],
            'responseImplementation' => [
                '<?php
                    echo (new Symfony\Component\HttpClient\Response\MockResponse())->getContent();',
                [
                    'TaintedHtml on line 2',
                    'TaintedTextWithQuotes on line 2',
                ],
            ],
            'streamedChunks' => [
                '<?php
                    use Symfony\Contracts\HttpClient\HttpClientInterface;

                    function fetch(HttpClientInterface $client): void {
                        $response = $client->request("GET", "https://example.com");
                        foreach ($client->stream($response) as $chunk) {
                            echo $chunk->getContent();
                            echo $chunk->getOffset();
                        }
                    }',
                [
                    'TaintedHtml on line 7',
                    'TaintedTextWithQuotes on line 7',
                ],
            ],
        ];
    }
}
