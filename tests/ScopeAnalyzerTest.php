<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\Attributes\Test;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\While_;
use PhpParser\ParserFactory;
use Psalm\Internal\Analyzer\ScopeAnalyzer;

final class ScopeAnalyzerTest extends TestCase
{
    /**
     * @return list<string>
     */
    private static function switchActions(string $cases): array
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $stmts = $parser->parse('<?php while (true) { switch ($mode) {' . $cases . '} }');

        self::assertNotNull($stmts);
        self::assertCount(1, $stmts);
        self::assertInstanceOf(While_::class, $stmts[0]);
        self::assertCount(1, $stmts[0]->stmts);
        $switch = $stmts[0]->stmts[0];
        self::assertInstanceOf(Switch_::class, $switch);

        // Pass the enclosing loop explicitly to test how switch actions propagate outwards.
        return ScopeAnalyzer::getControlActions([$switch], null, ['loop'], false);
    }

    #[Test]
    public function testBreakTwoIsPreservedBeforeBreakingDefault(): void
    {
        $actions = self::switchActions('case 0: break 2; default: break;');

        self::assertContains(ScopeAnalyzer::ACTION_LEAVE_LOOP, $actions);
        self::assertContains(ScopeAnalyzer::ACTION_NONE, $actions);
    }

    #[Test]
    public function testReturnIsPreservedBeforeBreakingDefault(): void
    {
        $actions = self::switchActions('case 0: return 1; default: break;');

        self::assertContains(ScopeAnalyzer::ACTION_RETURN, $actions);
        self::assertContains(ScopeAnalyzer::ACTION_NONE, $actions);
    }

    #[Test]
    public function testContinueTwoDoesNotCountAsLeavingLoop(): void
    {
        $actions = self::switchActions('case 0: continue 2; default: break;');

        self::assertNotContains(ScopeAnalyzer::ACTION_LEAVE_LOOP, $actions);
    }

    #[Test]
    public function testFallthroughMarkerDoesNotLeakPastReturn(): void
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $stmts = $parser->parse('<?php switch ($x) { case 0: break; default: ++$x; } return $x;');
        self::assertNotNull($stmts);

        self::assertSame(
            [ScopeAnalyzer::ACTION_RETURN],
            ScopeAnalyzer::getControlActions($stmts, null, [], false),
        );
    }

    #[Test]
    public function testDeeplyNestedSwitchesDoNotDuplicateExitActions(): void
    {
        // A switch can return, break or continue an enclosing loop. Without deduplication,
        // collecting both aggregated and escaping actions doubles the list at each level.
        $body = '++$mode;';
        for ($depth = 20; $depth >= 1; --$depth) {
            $body = 'switch ($mode) {'
                . 'case 0: break;'
                . 'case 1: ' . $body . ' continue ' . ($depth + 1) . ';'
                . 'default: return 1;'
                . '}';
        }

        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $stmts = $parser->parse('<?php while (true) {' . $body . '}');
        self::assertNotNull($stmts);
        self::assertCount(1, $stmts);
        self::assertInstanceOf(While_::class, $stmts[0]);

        self::assertSame(
            [ScopeAnalyzer::ACTION_RETURN, ScopeAnalyzer::ACTION_NONE],
            ScopeAnalyzer::getControlActions($stmts[0]->stmts, null, ['loop'], false),
        );
    }
}
