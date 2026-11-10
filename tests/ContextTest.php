<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Type;

final class ContextTest extends TestCase
{
    public function testScopeSummaryPreservesBinaryLiterals(): void
    {
        $context = new Context();
        $context->vars_in_scope['$this->bom'] = Type::getString("\xFF\xFE");
        $summary = $context->getScopeSummary();
        self::assertSame($summary, $context->getScopeSummary());

        $context->vars_in_scope['$this->bom'] = Type::getString("\xFE\xFF");
        $other_summary = $context->getScopeSummary();
        self::assertNotSame($summary, $other_summary);
        self::assertSame($other_summary, $context->getScopeSummary());

        $context->vars_in_scope['$this->bom'] = Type::getString("\u{FFFD}\u{FFFD}");
        self::assertNotSame($summary, $context->getScopeSummary());
        self::assertNotSame($other_summary, $context->getScopeSummary());
        self::assertSame($context->getScopeSummary(), $context->getScopeSummary());
    }

    public function testScopeSummaryPreservesBinaryVariableNames(): void
    {
        $context = new Context();
        $context->vars_possibly_in_scope["$\xFF"] = true;
        $summary = $context->getScopeSummary();
        self::assertSame($summary, $context->getScopeSummary());

        $context->vars_possibly_in_scope = ["$\xFE" => true];
        self::assertNotSame($summary, $context->getScopeSummary());
        self::assertSame($context->getScopeSummary(), $context->getScopeSummary());
    }
}
