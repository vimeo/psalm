<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Context;
use Psalm\IssueBuffer;

use function str_repeat;

final class ComplexMethodTest extends TestCase
{
    public function testCallArgumentEdgesDoNotCountTowardsMethodComplexity(): void
    {
        $this->project_analyzer->getConfig()->limit_method_complexity = true;
        $this->project_analyzer->getCodebase()->reportUnusedVariables();

        // A helper declared far above a method that calls it many times: the edge from each call
        // into the helper's declared parameter is inter-procedural and must not be measured by its
        // line distance to the declaration, or the method's "graph size" and "average path length"
        // would be inflated by the sheer number of calls it makes.
        $code = '<?php
            final class Cruncher {
                private static function h(int $a, int $b, int $c, int $d): int {
                    return $a + $b + $c + $d;
                }
' . str_repeat("\n", 150) . '
                public static function run(int $x, int $y, int $z, int $w): int {
' . str_repeat('                    $x = self::h($x, $y, $z, $w);' . "\n", 60) . '
                    return $x;
                }
            }';

        $this->addFile('somefile.php', $code);
        $this->analyzeFile('somefile.php', new Context());

        self::assertSame(0, IssueBuffer::getErrorCount());
    }
}
