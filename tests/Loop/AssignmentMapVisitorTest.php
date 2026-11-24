<?php

declare(strict_types=1);

namespace Psalm\Tests\Loop;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use Psalm\Internal\PhpVisitor\AssignmentMapVisitor;

use function array_keys;
use function sort;

final class AssignmentMapVisitorTest extends TestCase
{
    /**
     * @param list<string> $expected_feeding
     */
    #[DataProvider('providerLoopBodies')]
    public function testFeedingVarIds(string $body, array $expected_feeding): void
    {
        $stmts = (new ParserFactory)->createForNewestSupportedVersion()->parse('<?php ' . $body);
        self::assertNotNull($stmts);

        $visitor = new AssignmentMapVisitor(null);
        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);
        $traverser->traverse($stmts);

        $feeding = array_keys($visitor->getFeedingVarIds());
        sort($feeding);

        self::assertSame($expected_feeding, $feeding);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     * @psalm-pure
     */
    public static function providerLoopBodies(): iterable
    {
        yield 'propertyReadBeforeItsWriteFeeds' => ['$p->a = $p->b; $p->b = 1;', ['$p->b']];
        yield 'propertyReadAfterItsWriteDoesNotFeed' => ['$p->b = 1; $p->a = $p->b;', []];
        yield 'staticPropertyReadAfterItsWriteDoesNotFeed' => ['P::$b = 1; P::$a = P::$b;', []];
        yield 'nestedPropertyReadAfterItsWriteDoesNotFeed' => ['$p->q->b = 1; $p->a = $p->q->b;', []];
        yield 'propertyWrittenInABranchFeeds' => ['if (f()) { $p->b = 1; } $p->a = $p->b;', ['$p->b']];
        yield 'propertyOfAReassignedObjectFeeds' => ['$p->b = 1; $p = f(); $p->a = $p->b;', ['$p', '$p->b']];
        yield 'propertyOfAnIntermediateReassignedObjectFeeds'
            => ['$p->q->b = 1; $p->q = f(); $p->a = $p->q->b;', ['$p->q', '$p->q->b']];
        yield 'propertyOfAnObjectReassignedInTheReadingExpressionFeeds'
            => ['$p->b = 1; $x = [$p = f(), $p->b];', ['$p', '$p->b']];
        yield 'propertyOfAForeachValueFeeds'
            => ['$p->b = 1; foreach (f() as $p) { $p->a = $p->b; }', ['$p', '$p->b']];
        yield 'propertyOfAnObjectPassedToACallFeeds' => ['$p->b = 1; f($p); $p->a = $p->b;', ['$p', '$p->b']];
        yield 'propertyWrittenThroughAnOffsetFeeds' => ['$p[0]->b = 1; $p->a = $p[1]->b;', ['$p->b']];
    }
}
