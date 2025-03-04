<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type\Union;

final class MethodReturnTypeProviderEvent
{
    /**
     * Use this hook for providing custom return type logic. If this plugin does not know what a method should return
     * but another plugin may be able to determine the type, return null. Otherwise return a mixed union type if
     * something should be returned, but can't be more specific.
     *
     * @param non-empty-list<Union>|null $template_type_parameters
     * @param lowercase-string $method_name_lowercase
     * @param lowercase-string $called_method_name_lowercase
     * @internal
     */
    public function __construct(
        public readonly StatementsSource $source,
        public readonly string $fq_classlike_name,
        public readonly string $method_name_lowercase,
        public readonly PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $stmt,
        public readonly Context $context,
        public readonly CodeLocation $code_location,
        public readonly ?array $template_type_parameters = null,
        public readonly ?string $called_fq_classlike_name = null,
        public readonly ?string $called_method_name_lowercase = null,
    ) {
    }

    /**
     * @return list<PhpParser\Node\Arg>
     */
    public function getCallArgs(): array
    {
        return $this->stmt->getArgs();
    }
}