<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use Psalm\Internal\Json\Json;
use Psalm\Tests\TestCase;

final class JsonTest extends TestCase
{
    public function testConvertsInvalidUtf(): void
    {
        $invalidUtf = "\xd1"; // incomplete sequence like "ы"[0]
        $this->assertEquals('{"data":"<Invalid UTF-8: 0xd1>"}', Json::encode(["data" => $invalidUtf]));
    }
}
