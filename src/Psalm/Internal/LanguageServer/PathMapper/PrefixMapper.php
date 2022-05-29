<?php

namespace Psalm\Internal\LanguageServer\PathMapper;

use function array_flip;
use function str_starts_with;
use function strlen;
use function substr;
use function uksort;

/** @internal */
final class PrefixMapper implements PathMapperInterface
{
    /** @var array<string, string> */
    private $client_to_server = [];

    /** @var array<string, string> */
    private $server_to_client = [];

    /**
     * @param array<string, string> $mapping
     */
    public function __construct(array $mapping)
    {
        uksort(
            $mapping,
            function (string $a, string $b): int {
                return strlen($b) - strlen($a);
            }
        );
        $this->client_to_server = $mapping;
        $this->server_to_client = array_flip($mapping);
    }

    public function mapFromClient(string $path, string $client_root): string
    {
        foreach ($this->client_to_server as $from => $to) {
            if (str_starts_with($from, '{root}')) {
                $from = $client_root . substr($from, 6);
            }
            if (str_starts_with($path, $from)) {
                return $to . substr($path, strlen($from));
            }
        }
        return $path;
    }

    public function mapToClient(string $path, string $client_root): string
    {
        foreach ($this->server_to_client as $from => $to) {
            if (str_starts_with($to, '{root}')) {
                $to = $client_root . substr($to, 6);
            }
            if (str_starts_with($path, $from)) {
                return $to . substr($path, strlen($from));
            }
        }
        return $path;
    }
}
