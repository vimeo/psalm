<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Type\TemplateDefaults;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

/**
 * Gives the generic types written with fewer type arguments than their class has templates (`Foo<A>`), at any
 * depth of a type, the defaults of the templates they leave out, for the readers of stored types that don't
 * expand them. With a codebase, the defaults are expanded (see {@see TemplateDefaults::fillAndExpandTypeArguments()});
 * without, the generic types in them are filled the same way (see {@see TemplateDefaults::fillTypeArgumentsWith()}).
 *
 * @internal
 */
final class TemplateDefaultFiller extends MutableTypeVisitor
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly ClassLikeStorageProvider $classlike_storage_provider,
        private readonly ?Codebase $codebase,
    ) {
    }

    /**
     * @psalm-impure
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if (!$type instanceof TGenericObject || !$this->classlike_storage_provider->has($type->value)) {
            return null;
        }

        $type_params = $type->type_params;
        $this->traverseArray($type_params);

        $storage = $this->classlike_storage_provider->get($type->value);
        $type_params = $this->codebase === null
            ? TemplateDefaults::fillTypeArgumentsWith(
                $type_params,
                $storage,
                null,
                $type->from_docblock,
                function (Union $default): Union {
                    $this->traverse($default);

                    return $default;
                },
            )
            : TemplateDefaults::fillAndExpandTypeArguments(
                $type_params,
                $storage,
                $this->codebase,
                $type->from_docblock,
            );

        $type = $type->setTypeParams($type_params);

        return self::DONT_TRAVERSE_CHILDREN;
    }
}
