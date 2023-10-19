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
        private readonly StatementsSource $source,
        private readonly string $fq_classlike_name,
        private readonly string $method_name_lowercase,
        private readonly PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $stmt,
        private readonly Context $context,
        private readonly CodeLocation $code_location,
        private readonly ?array $template_type_parameters = null,
        private readonly ?string $called_fq_classlike_name = null,
        private readonly ?string $called_method_name_lowercase = null,
    ) {
    }

    public function getSource(): StatementsSource
    {
        return $this->source;
    }

    public function getFqClasslikeName(): string
    {
        return $this->fq_classlike_name;
    }

    /**
     * @return lowercase-string
     */
    public function getMethodNameLowercase(): string
    {
        return $this->method_name_lowercase;
    }

    /**
     * @return list<PhpParser\Node\Arg>
     */
    public function getCallArgs(): array
    {
        return $this->stmt->getArgs();
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getCodeLocation(): CodeLocation
    {
        return $this->code_location;
    }

    /**
     * @return non-empty-list<Union>|null
     */
    public function getTemplateTypeParameters(): ?array
    {
        return $this->template_type_parameters;
    }

    public function getCalledFqClasslikeName(): ?string
    {
        return $this->called_fq_classlike_name;
    }

    /**
     * @return lowercase-string|null
     */
    public function getCalledMethodNameLowercase(): ?string
    {
        return $this->called_method_name_lowercase;
    }

    public function getStmt(): PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall
    {
        return $this->stmt;
    }
}
