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
        self::assertNotSame($summary, $context->getScopeSummary());

        $context->vars_in_scope['$this->bom'] = Type::getString("\u{FFFD}\u{FFFD}");
        self::assertNotSame($summary, $context->getScopeSummary());
    }

    public function testScopeSummaryPreservesBinaryVariableNames(): void
    {
        $context = new Context();
        $context->vars_possibly_in_scope["$\xFF"] = true;
        $summary = $context->getScopeSummary();

        $context->vars_possibly_in_scope = ["$\xFE" => true];
        self::assertNotSame($summary, $context->getScopeSummary());
    }

    public function testScopeSummaryDistinguishesPossibleAndKnownVariables(): void
    {
        $context = new Context();
        $empty_summary = $context->getScopeSummary();
        $context->vars_possibly_in_scope['$value'] = true;
        $possible_summary = $context->getScopeSummary();
        self::assertNotSame($empty_summary, $possible_summary);

        $context->vars_in_scope['$value'] = Type::getString('value');
        self::assertNotSame($possible_summary, $context->getScopeSummary());
        self::assertSame($context->getScopeSummary(), $context->getScopeSummary());
    }
}
