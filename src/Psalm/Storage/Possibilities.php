<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\Codebase;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function is_string;
use function str_replace;
use function strtolower;

/**
 * @psalm-capabilities read-props|write-this-props|write-props|write-refs
 * @api
 */
final class Possibilities
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param list<Assertion> $rule
     * @psalm-mutation-free
     */
    public function __construct(
        /**
         * @var int|string the id of the property/variable, or
         *  the parameter offset of the affected arg
         */
        public int|string $var_id,
        public array $rule,
    ) {
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public function getUntemplatedCopy(
        TemplateResult $template_result,
        ?string $this_var_id,
        ?Codebase $codebase,
        ?Atomic $static_type = null,
    ): self {
        $assertion_rules = [];

        foreach ($this->rule as $assertion) {
            $assertion_type = $assertion->getAtomicType();

            if ($assertion_type) {
                // `static` of the called method is the class it is called on; one a template is bound to is the
                // caller's
                if ($static_type !== null) {
                    $assertion_type = self::resolveStatic($assertion_type, $static_type, $codebase);
                }

                $union = new Union([$assertion_type]);
                $union = TemplateInferredTypeReplacer::replace(
                    $union,
                    $template_result,
                    $codebase,
                );

                foreach ($union->getAtomicTypes() as $atomic_type) {
                    $assertion = $assertion->setAtomicType($atomic_type);
                    $assertion_rules[] = $assertion;
                }
            } else {
                $assertion_rules[] = $assertion;
            }
        }

        return new Possibilities(
            is_string($this->var_id) && $this_var_id
                ? str_replace('$this->', $this_var_id . '->', $this->var_id)
                : $this->var_id,
            $assertion_rules,
        );
    }

    /**
     * The asserted type with `static` replaced by the class the method is called on, keeping its type arguments and
     * the types the assertion intersects it with (`static&Marker`)
     *
     * @psalm-capabilities read-props
     */
    private static function resolveStatic(Atomic $asserted_type, Atomic $static_type, ?Codebase $codebase): Atomic
    {
        if (!$asserted_type instanceof TNamedObject
            || (!$asserted_type->is_static && strtolower($asserted_type->value) !== 'static')
        ) {
            return $asserted_type;
        }

        if ($asserted_type instanceof TGenericObject
            && $static_type instanceof TNamedObject
            && !$static_type instanceof TGenericObject
            && $codebase !== null
            && $codebase->classlike_storage_provider->has($static_type->value)
            && $codebase->classlike_storage_provider->get($static_type->value)->template_types !== null
        ) {
            $static_type = new TGenericObject(
                $static_type->value,
                $asserted_type->type_params,
                false,
                $static_type->is_static,
                $static_type->extra_types,
            );
        }

        if (!$static_type instanceof TNamedObject && !$static_type instanceof TTemplateParam) {
            return $static_type;
        }

        $extra_types = $static_type->extra_types + $asserted_type->extra_types;

        // `static&Marker` is `Marker` flagged as static: the class the call is on doesn't always implement it
        if ($codebase !== null
            && strtolower($asserted_type->value) !== 'static'
            && !self::isAlwaysA($static_type, $asserted_type->value, $codebase)
        ) {
            $intersected = new TNamedObject($asserted_type->value);
            $extra_types[$intersected->getKey()] = $intersected;
        }

        return $extra_types ? $static_type->setIntersectionTypes($extra_types) : $static_type;
    }

    /**
     * Whether the object the method is called on is always a $class_name, the bound of a template receiver included
     *
     * @psalm-capabilities read-props
     */
    private static function isAlwaysA(
        TNamedObject|TTemplateParam $receiver,
        string $class_name,
        Codebase $codebase,
    ): bool {
        $receiver_types = $receiver instanceof TTemplateParam ? $receiver->as->getAtomicTypes() : [$receiver];

        foreach ($receiver_types as $receiver_type) {
            if (!$receiver_type instanceof TNamedObject && !$receiver_type instanceof TTemplateParam) {
                return false;
            }

            if ($receiver_type instanceof TTemplateParam) {
                if (!self::isAlwaysA($receiver_type, $class_name, $codebase)) {
                    return false;
                }

                continue;
            }

            if (strtolower($receiver_type->value) !== strtolower($class_name)
                && (!$codebase->classlike_storage_provider->has($receiver_type->value)
                    || !$codebase->classExtendsOrImplements($receiver_type->value, $class_name))
            ) {
                return false;
            }
        }

        return true;
    }
}
