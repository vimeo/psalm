<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type\Union;

/**
 * @psalm-immutable
 * @api
 */
final class MethodReturnTypeProviderEvent
{
    /**
     * Use this hook for providing custom return type logic. If this plugin does not know what a method should return
     * but another plugin may be able to determine the type, return null. Otherwise return a mixed union type if
     * something should be returned, but can't be more specific.
     *
     * @param non-empty-list<Union>|null $template_type_parameters
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly StatementsSource $source,
        private readonly int $fq_classlike_name,
        private readonly int $method_name,
        private readonly PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $stmt,
        private readonly Context $context,
        private readonly CodeLocation $code_location,
        private readonly ?array $template_type_parameters = null,
        private readonly ?int $called_fq_classlike_name = null,
        private readonly ?int $called_method_name = null,
    ) {
    }

    public function getSource(): StatementsSource
    {
        return $this->source;
    }

    /**
     * Interned class name id, as declared (class names are case-sensitive).
     */
    public function getFqClasslikeName(): int
    {
        return $this->fq_classlike_name;
    }

    /**
     * Interned method name id, as written (method names are case-sensitive, no case folding is applied).
     */
    public function getMethodName(): int
    {
        return $this->method_name;
    }

    /**
     * @return list<PhpParser\Node\Arg>
     * @psalm-mutation-free
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

    public function getCalledFqClasslikeName(): ?int
    {
        return $this->called_fq_classlike_name;
    }

    /**
     * Interned called method name id, as written (method names are case-sensitive, no case folding is applied).
     */
    public function getCalledMethodName(): ?int
    {
        return $this->called_method_name;
    }

    public function getStmt(): PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall
    {
        return $this->stmt;
    }
}
