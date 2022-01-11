<?php

namespace Psalm\Internal\LanguageServer\LanguageServerProtocol;

class ClientCapabilitiesWorkspace
{
    /**
     * The client supports `workspace/configuration` requests.
     *
     * @var boolean|null
     * @psalm-suppress PossiblyUnusedProperty
     */
    public $configuration;

    /**
     * The client has support for workspace folders.
     *
     * @var boolean|null
     * @psalm-suppress PossiblyUnusedProperty
     */
    public $workspaceFolders;

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(bool $configuration = null, bool $workspaceFolders = null)
    {
        $this->configuration = $configuration;
        $this->workspaceFolders = $workspaceFolders;
    }
}
