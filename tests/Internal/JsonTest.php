<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use Psalm\Internal\Analyzer\DataFlowNodeData;
use Psalm\Internal\Json\Json;
use Psalm\Tests\TestCase;

final class JsonTest extends TestCase
{
    public function testConvertsInvalidUtf(): void
    {
        $invalidUtf = "\xd1"; // incomplete sequence like "ы"[0]
        $this->assertSame('{"data":"<Invalid UTF-8: 0xd1>"}', Json::encode(["data" => $invalidUtf]));
    }

    public function testConvertsInvalidUtfInTheTraceOfAnIssue(): void
    {
        $node = new DataFlowNodeData('$x', 1, 1, 'f.php', 'f.php', "echo \"\xd1\";", 0, 9, 0, 6, 9);
        $this->assertSame(
            '[{"label":"$x","line_from":1,"line_to":1,"file_name":"f.php","file_path":"f.php",'
                . '"snippet":"echo \\"<Invalid UTF-8: 0xd1>\\";","from":0,"to":9,"snippet_from":0,"column_from":6,'
                . '"column_to":9}]',
            Json::encode([$node]),
        );
    }
}
