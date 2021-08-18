<?php
namespace Psalm\Storage;

use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;

use function array_map;
use function implode;

class Assertion
{
    /**
     * @var array<int, array<int, string>> the rule being asserted
     */
    public $rule;

    /**
     * @var int|string the id of the property/variable, or
     *  the parameter offset of the affected arg
     */
    public $var_id;

    /**
     * @param string|int $var_id
     * @param array<int, array<int, string>> $rule
     */
    public function __construct($var_id, array $rule)
    {
        $this->rule = $rule;
        $this->var_id = $var_id;
    }

    /**
     * @param array<string, array<string, non-empty-list<TemplateBound>>> $inferred_lower_bounds
     */
    public function getUntemplatedCopy(
        array $inferred_lower_bounds,
        ?string $this_var_id,
        ?\Psalm\Codebase $codebase
    ) : self {
        return new Assertion(
            \is_string($this->var_id) && $this_var_id
                ? \str_replace('$this->', $this_var_id . '->', $this->var_id)
                : $this->var_id,
            array_map(
                /**
                 * @param array<int, string> $rules
                 *
                 * @return array<int, string>
                 */
                function (array $rules) use ($inferred_lower_bounds, $codebase) : array {
                    if (!$inferred_lower_bounds) {
                        return $rules;
                    }
                    return array_map(
                        function ($rule) use ($inferred_lower_bounds, $codebase) {
                            $rule_tokens = \Psalm\Internal\Type\TypeTokenizer::tokenize($rule);

                            $substitute = false;

                            foreach ($rule_tokens as &$rule_token) {
                                if (isset($inferred_lower_bounds[$rule_token[0]])) {
                                    foreach ($inferred_lower_bounds[$rule_token[0]] as $lower_bounds) {
                                        $substitute = true;

                                        $bound_type = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                                            $lower_bounds,
                                            $codebase
                                        );

                                        $first_type = \array_values($bound_type->getAtomicTypes())[0];

                                        if ($first_type instanceof \Psalm\Type\Atomic\TTemplateParam) {
                                            $rule_token[0] = $first_type->param_name;
                                        } else {
                                            $rule_token[0] = $first_type->getKey();
                                        }
                                    }
                                }
                            }

                            if ($substitute) {
                                return implode(
                                    '',
                                    array_map(
                                        function ($f) {
                                            return $f[0];
                                        },
                                        $rule_tokens
                                    )
                                );
                            }
                            return $rule;
                        },
                        $rules
                    );
                },
                $this->rule
            )
        );
    }
}
