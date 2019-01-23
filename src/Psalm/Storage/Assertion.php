<?php
namespace Psalm\Storage;

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
    public function __construct($var_id, $rule)
    {
        $this->rule = $rule;
        $this->var_id = $var_id;
    }

    /**
     * @param array<string, array{0:\Psalm\Type\Union, 1:null|string}> $template_type_map
     */
    public function getUntemplatedCopy(array $template_type_map) : self
    {
        return new Assertion(
            $this->var_id,
            array_map(
                /**
                 * @param array<int, string> $rules
                 */
                function (array $rules) use ($template_type_map) : array {
                    $first_rule = $rules[0];

                    $rule_tokens = \Psalm\Type::tokenize($first_rule);

                    if ($template_type_map) {
                        foreach ($rule_tokens as &$rule_token) {
                            if (isset($template_type_map[$rule_token])) {
                                $rule_token = $template_type_map[$rule_token][0]->getId();
                            }
                        }
                    }

                    return [implode('', $rule_tokens)];
                },
                $this->rule
            )
        );
    }
}
