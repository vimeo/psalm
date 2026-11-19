<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;
use Psalm\Type\Union;

/**
 * @internal
 */
final class ConditionalTemplateVisitor extends TypeVisitor
{
    private function __construct(private readonly TTemplateParam $template)
    {
    }

    public static function contains(Union $type, TTemplateParam $template): bool
    {
        return !(new self($template))->traverse($type);
    }

    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TConditional
            && $type->param_name === $this->template->param_name
            && $type->defining_class === $this->template->defining_class
        ) {
            return self::STOP_TRAVERSAL;
        }
        return null;
    }
}
