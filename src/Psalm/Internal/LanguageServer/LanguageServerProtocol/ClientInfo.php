<?php

namespace Psalm\Internal\LanguageServer\LanguageServerProtocol;

/**
 * @psalm-suppress UnusedClass
 */
class ClientInfo
{
    /**
     * The name of the client as defined by the client.
     *
     * @var string|null
     * @psalm-suppress PossiblyUnusedProperty
     */
    public $name;

    /**
     * The client's version as defined by the client.
     *
     * @var string|null
     * @psalm-suppress PossiblyUnusedProperty
     */
    public $version;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(string $name = null, string $version = null)
    {
        $this->name = $name;
        $this->version = $version;
    }
}
