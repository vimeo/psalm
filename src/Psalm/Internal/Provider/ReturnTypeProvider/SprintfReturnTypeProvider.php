<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use ArgumentCountError;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\RedundantFunctionCall;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Union;
use ValueError;

use function array_fill;
use function array_pop;
use function count;
use function is_string;
use function preg_match;
use function sprintf;
use function strlen;

/**
 * @internal
 */
final class SprintfReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return [
            'printf',
            'sprintf',
        ];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();

        // invalid - will already report an error for the params anyway
        if (count($call_args) < 1) {
            return null;
        }

        $has_splat_args = false;
        $node_type_provider = $statements_source->getNodeTypeProvider();
        foreach ($call_args as $call_arg) {
            $type = $node_type_provider->getType($call_arg->value);
            if ($type === null) {
                continue;
            }

            // if it's an array, used with splat operator
            // we cannot validate it reliably below and report false positive errors
            if ($type->isArray()) {
                $has_splat_args = true;
                break;
            }
        }

        // there is only 1 array argument, fall back to the default handling
        // eventually this could be refined
        // to check if it's an array with literal string as first element for further checking
        if (count($call_args) === 1 && $has_splat_args === true) {
            IssueBuffer::maybeAdd(
                new RedundantFunctionCall(
                    'Using the splat operator is redundant, as v' . $event->getFunctionId()
                    . ' without splat operator can be used instead of ' . $event->getFunctionId(),
                    $event->getCodeLocation(),
                ),
                $statements_source->getSuppressedIssues(),
            );

            return null;
        }

        // it makes no sense to use sprintf when there is only 1 arg (the format)
        // as it wouldn't have any placeholders
        // if it's a literal string, we can check it further though!
        $first_arg_type = $node_type_provider->getType($call_args[0]->value);
        if (count($call_args) === 1
            && ($first_arg_type === null || !$first_arg_type->isSingleStringLiteral())) {
            IssueBuffer::maybeAdd(
                new RedundantFunctionCall(
                    'Using ' . $event->getFunctionId()
                    . ' with a single argument is redundant, since there are no placeholder params to be substituted',
                    $event->getCodeLocation(),
                ),
                $statements_source->getSuppressedIssues(),
            );

            return null;
        }

        // PHP 7 handling for formats that do not contain anything but placeholders
        $is_falsable = true;
        foreach ($call_args as $index => $call_arg) {
            $type = $node_type_provider->getType($call_arg->value);

            if ($type === null && $index === 0 && $event->getFunctionId() === 'printf') {
                // printf only has the format validated above
                // don't change the return type
                break;
            }

            if ($type === null) {
                continue;
            }

            if ($index === 0 && $type->isSingleStringLiteral()) {
                if ($type->getSingleStringLiteral()->value === '') {
                    IssueBuffer::maybeAdd(
                        new RedundantFunctionCall(
                            'Calling ' . $event->getFunctionId() . ' with an empty first argument does nothing',
                            $event->getCodeLocation(),
                        ),
                        $statements_source->getSuppressedIssues(),
                    );

                    if ($event->getFunctionId() === 'printf') {
                        return Type::getInt(false, 0);
                    }

                    return Type::getString('');
                }

                // there are probably additional formats that return an empty string, this is just a starting point
                if (preg_match('/^%(?:\d+\$)?[-+]?0(?:\.0)?s$/', $type->getSingleStringLiteral()->value) === 1) {
                    IssueBuffer::maybeAdd(
                        new InvalidArgument(
                            'The pattern of argument 1 of ' . $event->getFunctionId()
                            . ' will always return an empty string',
                            $event->getCodeLocation(),
                            $event->getFunctionId(),
                        ),
                        $statements_source->getSuppressedIssues(),
                    );

                    if ($event->getFunctionId() === 'printf') {
                        return Type::getInt(false, 0);
                    }

                    return Type::getString('');
                }

                // these placeholders are too complex to handle for now
                if (preg_match(
                    '/%(?:\d+\$)?[-+]?(?:\d+|\*)(?:\.(?:\d+|\*))?[bcdouxXeEfFgGhHs]/',
                    $type->getSingleStringLiteral()->value,
                ) === 1) {
                    return null;
                }

                // assume a random, high number for tests
                $provided_placeholders_count = $has_splat_args === true ? 100 : count($call_args) - 1;
                $dummy = array_fill(0, $provided_placeholders_count, '');

                // check if we have enough/too many arguments and a valid format
                $initial_result = null;
                while (count($dummy) > -1) {
                    $result = null;
                    try {
                        // before PHP 8, an uncatchable Warning is thrown if too few arguments are passed
                        // which is ignored and handled below instead
                        $result = @sprintf($type->getSingleStringLiteral()->value, ...$dummy);
                        if ($initial_result === null) {
                            $initial_result = $result;

                            if ($result === $type->getSingleStringLiteral()->value) {
                                if (count($call_args) > 1) {
                                    // we need to report this here too, since we return early without further validation
                                    // otherwise people who have suspended RedundantFunctionCall errors
                                    // will not get an error for this
                                    IssueBuffer::maybeAdd(
                                        new TooManyArguments(
                                            'Too many arguments for the number of placeholders in '
                                            . $event->getFunctionId(),
                                            $event->getCodeLocation(),
                                            $event->getFunctionId(),
                                        ),
                                        $statements_source->getSuppressedIssues(),
                                    );
                                }

                                // the same error as above, but we have validated the pattern now
                                if (count($call_args) === 1) {
                                    IssueBuffer::maybeAdd(
                                        new RedundantFunctionCall(
                                            'Using ' . $event->getFunctionId()
                                            . ' with a single argument is redundant,'
                                            . ' since there are no placeholder params to be substituted',
                                            $event->getCodeLocation(),
                                        ),
                                        $statements_source->getSuppressedIssues(),
                                    );
                                } else {
                                    IssueBuffer::maybeAdd(
                                        new RedundantFunctionCall(
                                            'Argument 1 of ' . $event->getFunctionId()
                                            . ' does not contain any placeholders',
                                            $event->getCodeLocation(),
                                        ),
                                        $statements_source->getSuppressedIssues(),
                                    );
                                }

                                if ($event->getFunctionId() === 'printf') {
                                    return Type::getInt(false, strlen($type->getSingleStringLiteral()->value));
                                }

                                return $type;
                            }
                        }
                    } catch (ValueError $value_error) {
                        // PHP 8
                        // the format is invalid
                        IssueBuffer::maybeAdd(
                            new InvalidArgument(
                                'Argument 1 of ' . $event->getFunctionId() . ' is invalid - '
                                . $value_error->getMessage(),
                                $event->getCodeLocation(),
                                $event->getFunctionId(),
                            ),
                            $statements_source->getSuppressedIssues(),
                        );

                        break 2;
                    } catch (ArgumentCountError $error) {
                        // PHP 8
                        if (count($dummy) === $provided_placeholders_count) {
                            IssueBuffer::maybeAdd(
                                new TooFewArguments(
                                    'Too few arguments for ' . $event->getFunctionId(),
                                    $event->getCodeLocation(),
                                    $event->getFunctionId(),
                                ),
                                $statements_source->getSuppressedIssues(),
                            );

                            break 2;
                        }
                    }

                    if ($result === false && count($dummy) === $provided_placeholders_count) {
                        // could be invalid format or too few arguments
                        // we cannot distinguish this in PHP 7 without additional checks
                        $max_dummy = array_fill(0, 100, '');
                        $result = @sprintf($type->getSingleStringLiteral()->value, ...$max_dummy);
                        if ($result === false) {
                            // the format is invalid
                            IssueBuffer::maybeAdd(
                                new InvalidArgument(
                                    'Argument 1 of ' . $event->getFunctionId() . ' is invalid',
                                    $event->getCodeLocation(),
                                    $event->getFunctionId(),
                                ),
                                $statements_source->getSuppressedIssues(),
                            );
                        } else {
                            IssueBuffer::maybeAdd(
                                new TooFewArguments(
                                    'Too few arguments for ' . $event->getFunctionId(),
                                    $event->getCodeLocation(),
                                    $event->getFunctionId(),
                                ),
                                $statements_source->getSuppressedIssues(),
                            );
                        }

                        return Type::getFalse();
                    }

                    // we can only validate the format and arg 1 when using splat
                    if ($has_splat_args === true) {
                        break;
                    }

                    if (is_string($result) && count($dummy) + 1 <= $provided_placeholders_count) {
                        IssueBuffer::maybeAdd(
                            new TooManyArguments(
                                'Too many arguments for the number of placeholders in ' . $event->getFunctionId(),
                                $event->getCodeLocation(),
                                $event->getFunctionId(),
                            ),
                            $statements_source->getSuppressedIssues(),
                        );

                        break;
                    }

                    if (!is_string($result)) {
                        break;
                    }

                    // abort if it's empty, since we checked everything
                    if (array_pop($dummy) === null) {
                        break;
                    }
                }

                if ($event->getFunctionId() === 'printf') {
                    // printf only has the format validated above
                    // don't change the return type
                    return null;
                }

                if ($initial_result !== null && $initial_result !== false && $initial_result !== '') {
                    return Type::getNonEmptyString();
                }

                // if we didn't have any valid result
                // the pattern is invalid or not yet supported by the return type provider
                if ($initial_result === null || $initial_result === false) {
                    return null;
                }

                // the initial result is an empty string
                // which means the format is valid and it depends on the args, whether it is non-empty-string or not
                $is_falsable = false;
            }

            if ($index === 0 && $event->getFunctionId() === 'printf') {
                // printf only has the format validated above
                // don't change the return type
                break;
            }

            if ($index === 0) {
                continue;
            }

            // if the function has more arguments than the pattern has placeholders, this could be a false positive
            // if the param is not used in the pattern
            if ($type->isNonEmptyString() || $type->isInt() || $type->isFloat()) {
                return Type::getNonEmptyString();
            }

            // check for unions of either
            $atomic_types = $type->getAtomicTypes();
            if ($atomic_types === []) {
                continue;
            }

            foreach ($atomic_types as $atomic_type) {
                if ($atomic_type instanceof TNonEmptyString
                    || $atomic_type instanceof TClassString
                    || ($atomic_type instanceof TLiteralString && $atomic_type->value !== '')
                    || $atomic_type instanceof TInt
                    || $atomic_type instanceof TFloat
                    || $atomic_type instanceof TNumeric) {
                    // valid non-empty types, potentially there are more though
                    continue;
                }

                // empty or generic string
                // or other unhandled type
                continue 2;
            }

            return Type::getNonEmptyString();
        }

        if ($is_falsable === false) {
            return Type::getString();
        }

        return null;
    }
}
