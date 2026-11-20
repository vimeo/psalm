<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

use function in_array;

/**
 * Finds a type that is replaced by what one of the given templates was inferred as, not by a stand-in:
 * `key-of<T>`, `value-of<T>` and `T[K]`
 *
 * @internal
 */
final class ContainsTemplateReaderVisitor extends TypeVisitor
{
    private bool $contains_template_reader = false;

    /**
     * @param array<TTemplateParam> $templates
     * @psalm-mutation-free
     */
    public function __construct(private readonly array $templates)
    {
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TTemplateKeyOf || $type instanceof TTemplateValueOf) {
            $param_names = [$type->param_name];
        } elseif ($type instanceof TTemplateIndexedAccess) {
            $param_names = [$type->array_param_name, $type->offset_param_name];
        } else {
            return null;
        }

        foreach ($this->templates as $template) {
            if ($template->defining_class === $type->defining_class
                && in_array($template->param_name, $param_names, true)
            ) {
                $this->contains_template_reader = true;

                return self::STOP_TRAVERSAL;
            }
        }

        return null;
    }

    public function matches(): bool
    {
        return $this->contains_template_reader;
    }
}
