<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\Codebase;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type\Atomic\TNamedObject;
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
        ?string $static_class = null,
    ): self {
        $assertion_rules = [];

        foreach ($this->rule as $assertion) {
            $assertion_type = $assertion->getAtomicType();

            if ($assertion_type) {
                $union = new Union([$assertion_type]);
                $union = TemplateInferredTypeReplacer::replace(
                    $union,
                    $template_result,
                    $codebase,
                );

                foreach ($union->getAtomicTypes() as $atomic_type) {
                    // `static` is the class the method is called on
                    if ($static_class !== null
                        && $atomic_type instanceof TNamedObject
                        && ($atomic_type->is_static || strtolower($atomic_type->value) === 'static')
                    ) {
                        $atomic_type = new TNamedObject($static_class);
                    }

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
}
