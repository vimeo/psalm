<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use Closure;
use PhpParser;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\RedundantCondition;
use Psalm\Issue\RedundantConditionGivenDocblockType;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Union;

use function array_key_exists;
use function array_map;

/**
 * The assertions of a call on one of several receiver types (`$a_or_b->check()`, `$a_or_b_class::check()`): each
 * receiver type asserts what it asserts on the variables as they were before the call, not after the assertions of
 * the others, and after the call the variables hold what they hold after the assertions of any of them
 *
 * @internal
 */
final class UnionReceiverAssertions
{
    private ?Context $receiver_context = null;

    /** @var array<string, true> */
    private array $receiver_asserted_var_ids = [];

    /**
     * What the assertions of each receiver type made of the variables they changed, null for those they removed
     *
     * @var list<array<string, ?Union>>
     */
    private array $asserted_types = [];

    /** @var list<CodeIssue> */
    private array $redundant_condition_issues = [];

    private function __construct(
        private readonly PhpParser\Node\Expr $call,
        private readonly ?self $previous,
    ) {
    }

    public static function startCall(
        PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $call,
        Context $context,
        int $receiver_count,
    ): ?self {
        if ($receiver_count < 2) {
            return null;
        }

        $receiver_assertions = new self($call, $context->union_receiver_assertions);

        $context->union_receiver_assertions = $receiver_assertions;

        return $receiver_assertions;
    }

    /**
     * The assertions of the call to the current receiver type, when the call has several
     *
     * @psalm-mutation-free
     */
    public static function forCall(PhpParser\Node\Expr $call, Context $context): ?self
    {
        $receiver_assertions = $context->union_receiver_assertions;

        return $receiver_assertions !== null && $receiver_assertions->call === $call ? $receiver_assertions : null;
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public function startReceiver(): void
    {
        $this->receiver_context = null;
        $this->receiver_asserted_var_ids = [];
    }

    /**
     * @param Closure(Context): void $apply_assertions
     */
    public function apply(Context $context, Closure $apply_assertions): void
    {
        $receiver_context = $this->receiver_context ??= clone $context;

        $type_ids_before = array_map(
            static fn(Union $type): string => $type->getId(true),
            $receiver_context->vars_in_scope,
        );

        IssueBuffer::startRecording();

        $apply_assertions($receiver_context);

        $recorded_issues = IssueBuffer::clearRecordingLevel();
        IssueBuffer::stopRecording();

        foreach ($recorded_issues as $recorded_issue) {
            if ($recorded_issue instanceof RedundantCondition
                || $recorded_issue instanceof RedundantConditionGivenDocblockType
            ) {
                $this->redundant_condition_issues[] = $recorded_issue;
            } else {
                IssueBuffer::bubbleUp($recorded_issue);
            }
        }

        foreach ($receiver_context->vars_in_scope as $var_id => $type) {
            if (!isset($type_ids_before[$var_id]) || $type_ids_before[$var_id] !== $type->getId(true)) {
                $this->receiver_asserted_var_ids[$var_id] = true;
            }
        }

        foreach ($type_ids_before as $var_id => $_) {
            if (!isset($receiver_context->vars_in_scope[$var_id])) {
                $this->receiver_asserted_var_ids[$var_id] = true;
            }
        }
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public function endReceiver(): void
    {
        $asserted_types = [];

        foreach ($this->receiver_asserted_var_ids as $var_id => $_) {
            $asserted_types[$var_id] = $this->receiver_context?->vars_in_scope[$var_id] ?? null;
        }

        $this->asserted_types[] = $asserted_types;
    }

    public function endCall(Context $context, Codebase $codebase): void
    {
        $context->union_receiver_assertions = $this->previous;

        $asserted_var_ids = [];

        foreach ($this->asserted_types as $asserted_types) {
            foreach ($asserted_types as $var_id => $_) {
                $asserted_var_ids[$var_id] = true;
            }
        }

        // a call is only redundant if what each receiver type asserts is
        if (!$asserted_var_ids) {
            foreach ($this->redundant_condition_issues as $redundant_condition_issue) {
                IssueBuffer::bubbleUp($redundant_condition_issue);
            }

            return;
        }

        foreach ($asserted_var_ids as $var_id => $_) {
            $type = null;

            foreach ($this->asserted_types as $asserted_types) {
                // a receiver type that asserts nothing of the variable leaves it as it is
                $receiver_type = array_key_exists($var_id, $asserted_types)
                    ? $asserted_types[$var_id]
                    : $context->vars_in_scope[$var_id] ?? null;

                if ($receiver_type === null) {
                    unset($context->vars_in_scope[$var_id]);

                    continue 2;
                }

                $type = Type::combineUnionTypes($type, $receiver_type, $codebase);
            }

            if ($type !== null) {
                $context->vars_in_scope[$var_id] = $type;
            }
        }
    }
}
