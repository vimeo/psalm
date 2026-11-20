<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Closure;
use Override;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosedResource;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TVoid;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;
use Psalm\Type\Union;

use function array_map;
use function assert;
use function count;
use function is_string;
use function preg_match;

/**
 * The properties of an object shape that a template names (`object{T: int}`). Until the template is bound they are
 * kept apart from the named properties, in `$template_keys` and `$template_properties`.
 *
 * @internal
 */
final class TemplateShapeKeys
{
    /**
     * @psalm-pure
     */
    public static function getKey(TTemplateParam $template): string
    {
        return $template->param_name . ':' . $template->defining_class;
    }

    /**
     * Whether some type the template can be bound to may be a key: a bound not expanded yet (`self::A`, `value-of<E>`)
     * may be one
     *
     * @psalm-pure
     */
    public static function canBeKey(TTemplateParam $template): bool
    {
        foreach ($template->as->getAtomicTypes() as $bound) {
            if ($bound instanceof TTemplateParam && !self::canBeKey($bound)) {
                continue;
            }

            if (!$bound instanceof TObject
                && !$bound instanceof TNamedObject
                && !$bound instanceof TFloat
                && !$bound instanceof TNumeric
                && !$bound instanceof TBool
                && !$bound instanceof TNull
                && !$bound instanceof TArray
                && !$bound instanceof TKeyedArray
                && !$bound instanceof TIterable
                && !$bound instanceof TCallable
                && !$bound instanceof TResource
                && !$bound instanceof TClosedResource
                && !$bound instanceof TVoid
                && !$bound instanceof TNever
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-pure
     */
    private static function singletonName(TTemplateParam $template): TLiteralString|TLiteralInt|null
    {
        $bound = $template->as;
        while (count($bound->getAtomicTypes()) === 1
            && ($atomic = $bound->getSingleAtomic()) instanceof TTemplateParam
        ) {
            $bound = $atomic->as;
        }

        $name = count($bound->getAtomicTypes()) === 1 ? $bound->getSingleAtomic() : null;
        return $name instanceof TLiteralString || $name instanceof TLiteralInt ? $name : null;
    }

    /**
     * @param Closure(Union): Union $expand
     */
    public static function expand(TObjectWithProperties $shape, Closure $expand): Atomic
    {
        $names = [];
        foreach ($shape->template_keys as $key => $template) {
            $template = $template->replaceAs($expand($template->as));
            // Keep a late duplicate visible to TypeChecker.
            $name = !isset($shape->properties[$template->param_name]) && !self::canBeKey($template)
                ? TLiteralString::make($template->param_name)
                : null;
            $names[$key] = new Union([$name ?? $template]);
        }

        $shape = $shape->setTemplateProperties(array_map($expand, $shape->template_properties));
        return self::rename($shape, $names, null, false);
    }

    /**
     * A singleton bound proves the name for containment, not a substitution into the stored shape.
     *
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public static function projectKnownNames(TObjectWithProperties $shape): TObjectWithProperties
    {
        $names = [];
        foreach ($shape->template_keys as $key => $template) {
            $name = self::singletonName($template);
            if ($name !== null) {
                $names[$key] = new Union([$name]);
            }
        }

        return $names ? self::renameShape($shape, $names, null, false) : $shape;
    }

    public static function getSuggestionId(Union $type): string
    {
        (new class extends MutableTypeVisitor {
            /**
             * @return null
             * @psalm-capabilities read-props|write-this-props|write-refs
             */
            #[Override]
            protected function enterNode(TypeNode &$type): ?int
            {
                if ($type instanceof TObjectWithProperties) {
                    $properties = [];
                    foreach ($type->properties as $name => $property) {
                        $name = is_string($name) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name) ? "'$name'" : $name;
                        $properties[$name] = $property;
                    }
                    $type = $type->setProperties($properties);
                }
                return null;
            }
        })->traverse($type);

        return $type->getId(false);
    }

    /**
     * @psalm-pure
     */
    public static function containsTemplateKeys(Union $type): bool
    {
        return !(new class extends TypeVisitor {
            /**
             * @return self::STOP_TRAVERSAL|null
             * @psalm-pure
             */
            #[Override]
            protected function enterNode(TypeNode $type): ?int
            {
                return $type instanceof TObjectWithProperties && $type->template_keys ? self::STOP_TRAVERSAL : null;
            }
        })->traverse($type);
    }

    /**
     * The templates naming properties of $type or of the shapes it is intersected with, by their key
     *
     * @return array<string, TTemplateParam>
     * @psalm-pure
     */
    public static function getTemplateKeys(Atomic $type): array
    {
        $template_keys = $type instanceof TObjectWithProperties ? $type->template_keys : [];

        if (Type::isIntersectionType($type)) {
            foreach ($type->extra_types as $extra_type) {
                if ($extra_type instanceof TObjectWithProperties) {
                    $template_keys += $extra_type->template_keys;
                }
            }
        }

        return $template_keys;
    }

    /**
     * $type with the properties templates name, its own and those of the shapes it is intersected with, under what
     * $names says each template, by its key, stands for: a literal names the property, another template takes it over,
     * and any other type leaves it out. When a template stands for several literals, each is required in a parameter
     * and optional in a return. A written literal property's value type takes precedence over a template's.
     * $replace, if given, replaces the templates in the types of those properties.
     *
     * @param array<string, Union> $names
     * @param ?Closure[read-props|write-this-props|write-props|write-refs](Union): Union $replace
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public static function rename(
        Atomic $type,
        array $names,
        ?Closure $replace,
        bool $in_param,
    ): Atomic {
        if ($type instanceof TObjectWithProperties && $type->template_keys) {
            $type = self::renameShape($type, $names, $replace, $in_param);

            if ($type->properties === []
                && $type->template_keys === []
                && $type->methods === []
                && $type->extra_types === []
            ) {
                return new TObject($type->from_docblock);
            }
        }

        if (!Type::isIntersectionType($type)) {
            return $type;
        }

        $extra_types = $type->extra_types;

        foreach ($extra_types as $key => $extra_type) {
            if (!$extra_type instanceof TObjectWithProperties || !$extra_type->template_keys) {
                continue;
            }
            $extra_type = self::renameShape($extra_type, $names, $replace, $in_param);
            if (!$extra_type->properties && !$extra_type->template_keys && !$extra_type->methods) {
                unset($extra_types[$key]);
            } else {
                $extra_types[$key] = $extra_type;
            }
        }

        return $type->setIntersectionTypes($extra_types);
    }

    /**
     * @param array<string, Union> $names
     * @param ?Closure[read-props|write-this-props|write-props|write-refs](Union): Union $replace
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    private static function renameShape(
        TObjectWithProperties $shape,
        array $names,
        ?Closure $replace,
        bool $in_param,
    ): TObjectWithProperties {
        $properties = $shape->properties;
        $narrowed_from = $shape->narrowed_from;
        $template_keys = [];
        $template_properties = [];

        foreach ($shape->template_properties as $key => $property_type) {
            $property_type = $replace === null ? $property_type : $replace($property_type);
            $key_names = isset($names[$key]) ? $names[$key]->getAtomicTypes() : [$shape->template_keys[$key]];

            if (count($key_names) > 1 && !$in_param) {
                $property_type = $property_type->setPossiblyUndefined(true);
            }

            foreach ($key_names as $name) {
                if ($name instanceof TTemplateParam) {
                    $name = $name->setIntersectionTypes([]);
                    $name_key = self::getKey($name);
                    $template_keys[$name_key] = $name;
                    $template_properties[$name_key] = self::merge(
                        $template_properties[$name_key] ?? null,
                        $property_type,
                    );
                } elseif ($name instanceof TLiteralString || $name instanceof TLiteralInt) {
                    $name = $name->value;
                    $properties[$name] = self::merge(
                        $properties[$name] ?? null,
                        $property_type,
                        isset($shape->properties[$name]),
                    );
                    if (isset($narrowed_from[$name]) && !$property_type->possibly_undefined) {
                        $narrowed_from[$name] = $narrowed_from[$name]->setPossiblyUndefined(false);
                    }
                }
            }
        }

        if ($template_properties === $shape->template_properties
            && $template_keys === $shape->template_keys
            && $properties === $shape->properties
        ) {
            return $shape;
        }

        return new TObjectWithProperties(
            $properties,
            $shape->methods,
            $shape->extra_types,
            $shape->from_docblock,
            $narrowed_from,
            $template_keys,
            $template_properties,
        );
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function merge(?Union $existing, Union $property_type, bool $written = false): Union
    {
        if ($existing === null) {
            return $property_type;
        }

        return ($written ? $existing : Type::combineUnionTypes($existing, $property_type))
            ->setPossiblyUndefined($existing->possibly_undefined && $property_type->possibly_undefined);
    }

    /**
     * The array of the properties of $shape, a cast or get_object_vars() gives: one a template names has one of the
     * keys the template allows, and makes the array non-empty if it is required
     *
     * @param array{Union, Union}|null $other_properties the key and value type of properties the shape doesn't list
     */
    public static function toArray(TObjectWithProperties $shape, ?array $other_properties): TArray|TKeyedArray
    {
        [$key_type, $value_type] = $other_properties ?? [null, null];
        $non_empty = false;

        foreach ($shape->template_keys as $key => $template) {
            $property_type = $shape->template_properties[$key];
            $non_empty = $non_empty || !$property_type->possibly_undefined;
            $key_type = Type::combineUnionTypes($key_type, new Union([self::getKeyType($template)]));
            $value_type = Type::combineUnionTypes($value_type, $property_type->setPossiblyUndefined(false));
        }

        assert($key_type !== null && $value_type !== null);
        $params = [$key_type, $value_type];
        if (!$shape->properties) {
            return $non_empty ? new TNonEmptyArray($params) : new TArray($params);
        }

        $array = TKeyedArray::make($shape->properties, null, $params);
        return $non_empty && $array instanceof TKeyedArray && !$array->isNonEmpty()
            ? new TNonEmptyArray($array->getGenericArrayType()->type_params)
            : $array;
    }

    /**
     * @psalm-pure
     */
    private static function getKeyType(TTemplateParam $template): Atomic
    {
        foreach ($template->as->getAtomicTypes() as $bound) {
            if (!$bound instanceof TString
                && !$bound instanceof TInt
                && !$bound instanceof TArrayKey
                && !$bound instanceof TTemplateParam
            ) {
                return new TArrayKey();
            }
        }

        return $template;
    }
}
