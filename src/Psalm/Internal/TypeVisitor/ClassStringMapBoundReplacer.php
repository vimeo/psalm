<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

use function array_shift;

/**
 * Replaces the template of a class-string-map by its bound, leaving every other template as it is
 *
 * @internal
 */
final class ClassStringMapBoundReplacer extends MutableTypeVisitor
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private readonly TClassStringMap $map,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function getKeyType(TClassStringMap $map): Union
    {
        return new Union([self::getBoundClassString($map)]);
    }

    public static function getValueType(TClassStringMap $map): Union
    {
        $value_type = $map->value_param;

        (new self($map))->traverse($value_type);

        return $value_type;
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TClassStringMap && $type->param_name === $this->map->param_name) {
            // an inner map declaring the same template name shadows it
            return self::DONT_TRAVERSE_CHILDREN;
        }

        if ($type instanceof TTemplateParam
            && $type->param_name === $this->map->param_name
            && $type->defining_class === 'class-string-map'
        ) {
            $extra_types = $type->extra_types;
            $bound = $this->map->as_type;

            if ($bound) {
                $type = $bound->setIntersectionTypes([...$bound->extra_types, ...$extra_types]);
            } elseif ($extra_types) {
                // object&Bar is Bar
                $type = array_shift($extra_types);
                $type = $extra_types ? $type->setIntersectionTypes([...$type->extra_types, ...$extra_types]) : $type;
            } else {
                $type = new TObject();
            }

            return self::DONT_TRAVERSE_CHILDREN;
        }

        if ($type instanceof TTemplateParamClass
            && $type->param_name === $this->map->param_name
            && $type->defining_class === 'class-string-map'
        ) {
            $type = self::getBoundClassString($this->map);

            return self::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }

    /**
     * @psalm-pure
     */
    private static function getBoundClassString(TClassStringMap $map): TClassString
    {
        return new TClassString($map->as_type->value ?? 'object', $map->as_type);
    }
}
