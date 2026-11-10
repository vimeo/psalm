<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Type;

use function json_encode;
use function serialize;

use const JSON_THROW_ON_ERROR;

final class ContextTest extends TestCase
{
    public function testScopeSummaryPreservesJsonForValidUtf8(): void
    {
        $context = new Context();
        self::assertSame(json_encode([], JSON_THROW_ON_ERROR), $context->getScopeSummary());

        $type = Type::getString("é/\"\\\n");
        $context->vars_possibly_in_scope = ['$possible' => true, '$known' => true];
        $context->vars_in_scope = ['$known' => $type, '$変数' => Type::getInt()];

        self::assertSame(
            json_encode(
                ['$possible' => true, '$known' => $type->getId(), '$変数' => 'int'],
                JSON_THROW_ON_ERROR,
            ),
            $context->getScopeSummary(),
        );
    }

    public function testScopeSummaryPreservesBinaryLiterals(): void
    {
        $context = new Context();
        $context->vars_in_scope['$this->bom'] = Type::getString("\xFF\xFE");
        $summary = $context->getScopeSummary();

        self::assertSame(serialize(['$this->bom' => $context->vars_in_scope['$this->bom']->getId()]), $summary);
        self::assertStringStartsWith('a:', $summary);
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

        self::assertSame(serialize(["$\xFF" => true]), $summary);
        self::assertStringStartsWith('a:', $summary);

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
