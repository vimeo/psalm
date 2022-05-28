<?php

namespace Psalm\Internal\LanguageServer\PathMapper;

use SimpleXMLElement;

use function array_flip;
use function str_starts_with;
use function strlen;
use function substr;
use function uksort;

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

    public function mapFromClient(string $path): string
    {
        return $this->map($this->client_to_server, $path);
    }

    public function mapToClient(string $path): string
    {
        return $this->map($this->server_to_client, $path);
    }

    /** @param array<string, string> $mapping */
    private function map(array $mapping, string $path): string
    {
        foreach ($mapping as $from => $to) {
            if (str_starts_with($path, $from)) {
                return $to . substr($path, strlen($from));
            }
        }
        return $path;
    }

    public static function fromConfigEntry(SimpleXMLElement $_elt): self
    {
        return new self([]);
    }
}
