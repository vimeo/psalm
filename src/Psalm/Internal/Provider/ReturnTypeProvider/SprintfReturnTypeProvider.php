<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use ArgumentCountError;
use Psalm\Issue\InvalidArgument;
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
use function sprintf;

/**
 * @internal
 */
class SprintfReturnTypeProvider implements FunctionReturnTypeProviderInterface
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

        // it makes no sense to use sprintf/printf when there is only 1 arg (the format)
        // as it wouldn't have any placeholders
        if (count($call_args) === 1) {
            IssueBuffer::maybeAdd(
                new TooFewArguments(
                    'Too few arguments for ' . $event->getFunctionId() . ', expecting at least 2 arguments',
                    $event->getCodeLocation(),
                    $event->getFunctionId(),
                ),
                $statements_source->getSuppressedIssues(),
            );

            return null;
        }

        $node_type_provider = $statements_source->getNodeTypeProvider();
        foreach ($call_args as $index => $call_arg) {
            $type = $node_type_provider->getType($call_arg->value);
            if ($type === null && $index === 0 && $event->getFunctionId() === 'printf') {
                break;
            }

            if ($type === null) {
                continue;
            }

            if ($index === 0 && $type->isSingleStringLiteral()) {
                if ($type->getSingleStringLiteral()->value === '') {
                    IssueBuffer::maybeAdd(
                        new InvalidArgument(
                            'Argument 1 of ' . $event->getFunctionId() . ' must not be an empty string',
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

                // there are probably additional formats that return an empty string, this is just a starting point
                if (preg_match('/^%(?:\d+\$)?[-+]?0(\.0)?s$/', $type->getSingleStringLiteral()->value) === 1) {
                    IssueBuffer::maybeAdd(
                        new InvalidArgument(
                            'The pattern of argument 1 of ' . $event->getFunctionId() . ' will always return an empty string',
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

                $args_count = count($call_args) - 1;
                $dummy = array_fill(0, $args_count, '');

                // check if we have enough/too many arguments and a valid format
                $initial_result = null;
                while (count($dummy) > -1) {
                    try {
                        // before PHP 8, an uncatchable Warning is thrown if too few arguments are passed
                        // which is ignored and handled below instead
                        $result = @sprintf($type->getSingleStringLiteral()->value, ...$dummy);
                        if ($initial_result === null) {
                            $initial_result = $result;

                            if ($result === $type->getSingleStringLiteral()->value) {
                                IssueBuffer::maybeAdd(
                                    new InvalidArgument(
                                        'Argument 1 of ' . $event->getFunctionId()
                                        . ' does not contain any placeholders',
                                        $event->getCodeLocation(),
                                        $event->getFunctionId(),
                                    ),
                                    $statements_source->getSuppressedIssues(),
                                );

                                return null;
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
                        if (count($dummy) >= $args_count) {
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

                        // we are in the next iteration, so we have 1 placeholder less here
                        // otherwise we would have reported an error above already
                        if (count($dummy) + 1 === $args_count) {
                            break;
                        }

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

                    /**
                     * PHP 7
                     *
                     * @psalm-suppress DocblockTypeContradiction
                     */
                    if ($result === false && count($dummy) >= $args_count) {
                        IssueBuffer::maybeAdd(
                            new TooFewArguments(
                                'Too few arguments for ' . $event->getFunctionId(),
                                $event->getCodeLocation(),
                                $event->getFunctionId(),
                            ),
                            $statements_source->getSuppressedIssues(),
                        );

                        return Type::getFalse();
                    }

                    /**
                     * PHP 7
                     *
                     * @psalm-suppress DocblockTypeContradiction
                     */
                    if ($result === false && count($dummy) + 1 !== $args_count) {
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

                    // for PHP 7, since it doesn't throw above
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

                /**
                 * PHP 7 can have false here
                 *
                 * @psalm-suppress RedundantConditionGivenDocblockType
                 */
                if ($initial_result !== null && $initial_result !== false && $initial_result !== '') {
                    return Type::getNonEmptyString();
                }

                // if we didn't have a valid result
                // the pattern is invalid or not yet supported by the return type provider
                if ($initial_result === null || $initial_result === false) {
                    return null;
                }
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
            // however this is already reported above and returned, so this cannot happen
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

        return null;
    }
}
