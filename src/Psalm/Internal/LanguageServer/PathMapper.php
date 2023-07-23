<?php

namespace Psalm\Internal\LanguageServer;

use function rtrim;
use function strlen;
use function substr;

/** @internal */
final class PathMapper
{
    private string $server_root;
    private ?string $client_root;

    public function __construct(string $server_root, ?string $client_root = null)
    {
        $this->server_root = $this->sanitizeFolderPath($server_root);
        $this->client_root = $this->sanitizeFolderPath($client_root);
    }

    public function configureClientRoot(string $client_root): void
    {
        // ignore if preconfigured
        if ($this->client_root === null) {
            $this->client_root = $this->sanitizeFolderPath($client_root);
        }
    }

    public function mapClientToServer(string $client_path): string
    {
        if ($this->client_root === null) {
            return $client_path;
        }

        if (substr($client_path, 0, strlen($this->client_root)) === $this->client_root) {
            return $this->server_root . substr($client_path, strlen($this->client_root));
        }

        return $client_path;
    }

    public function mapServerToClient(string $server_path): string
    {
        if ($this->client_root === null) {
            return $server_path;
        }
        if (substr($server_path, 0, strlen($this->server_root)) === $this->server_root) {
            return $this->client_root . substr($server_path, strlen($this->server_root));
        }
        return $server_path;
    }

    /** @return ($path is null ? null : string) */
    private function sanitizeFolderPath(?string $path): ?string
    {
        if ($path === null) {
            return $path;
        }
        return rtrim($path, '/');
    }
}
