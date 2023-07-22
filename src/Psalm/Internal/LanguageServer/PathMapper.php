<?php

namespace Psalm\Internal\LanguageServer;

/** @internal */
final class PathMapper
{
    private string $serverRoot;
    private ?string $clientRoot;

    public function __construct(string $serverRoot, ?string $clientRoot = null)
    {
        $this->serverRoot = $this->sanitizeFolderPath($serverRoot);
        $this->clientRoot = $this->sanitizeFolderPath($clientRoot);
    }

    public function configureClientRoot(string $clientRoot): void
    {
        // ignore if preconfigured
        if ($this->clientRoot === null) {
            $this->clientRoot = $this->sanitizeFolderPath($clientRoot);
        }
    }

    public function mapClientToServer(string $clientPath): string
    {
        if ($this->clientRoot === null) {
            return $clientPath;
        }

        if (substr($clientPath, 0, strlen($this->clientRoot)) === $this->clientRoot) {
            return $this->serverRoot . substr($clientPath, strlen($this->clientRoot));
        }

        return $clientPath;
    }

    public function mapServerToClient(string $serverPath): string
    {
        if ($this->clientRoot === null) {
            return $serverPath;
        }
        if (substr($serverPath, 0, strlen($this->serverRoot)) === $this->serverRoot) {
            return $this->clientRoot . substr($serverPath, strlen($this->serverRoot));
        }
        return $serverPath;
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
