<?php

namespace Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Type\TaintKindRegistry;

final class TaintKindFactory
{
    /**
     * @readonly
     */
    private TaintKindRegistry $registry;

    /**
     * @internal
     */
    public function __construct(TaintKindRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param array<int, string> $proxies
     */
    public function create(
        string $type,
        CodeLocation $code_location,
        array $journey,
        string $journey_text,
        array $proxies = []
    ): CodeIssue {
        /** @var TaintedInput $class_name */
        $class_name = $this->resolveKindProxies($type, $proxies)
            ?? $this->registry->getKind($type)
            ?? $this->registry->getDefaultKind();
        return new $class_name($class_name::MESSAGE, $code_location, $journey, $journey_text);
    }

    /**
     * @param array<int, string> $proxies
     */
    private function resolveKindProxies(string $type, array $proxies): ?string
    {
        if ($proxies === []) {
            return null;
        }
        foreach ($proxies as $proxy) {
            $class_name = $this->registry->getKindProxy($type, $proxy);
            if ($class_name !== null) {
                return $class_name;
            }
        }
        return null;
    }
}
