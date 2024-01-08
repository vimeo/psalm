<?php

namespace Psalm\Storage;

use Psalm\Codebase;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type\Union;

use function is_string;
use function str_replace;

final class Possibilities
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @var list<Assertion> the rule being asserted
     */
    public $rule;

    /**
     * @var int|string the id of the property/variable, or
     *  the parameter offset of the affected arg
     */
    public $var_id;

    /**
     * @param string|int $var_id
     * @param list<Assertion> $rule
     */
    public function __construct($var_id, array $rule)
    {
        $this->rule = $rule;
        $this->var_id = $var_id;
    }

    public function getUntemplatedCopy(
        TemplateResult $template_result,
        ?string $this_var_id,
        ?Codebase $codebase
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
