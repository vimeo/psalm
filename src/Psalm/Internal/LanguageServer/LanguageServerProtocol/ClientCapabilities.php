<?php

namespace Psalm\Internal\LanguageServer\LanguageServerProtocol;

class ClientCapabilities
{
    /**
     * The client supports `workspace/configuration` requests.
     *
     * @var ClientCapabilitiesWorkspace|null
     * @psalm-suppress PossiblyUnusedProperty
     */
    public $workspace;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(ClientCapabilitiesWorkspace $workspace = null)
    {
        $this->workspace = $workspace;
    }
}
