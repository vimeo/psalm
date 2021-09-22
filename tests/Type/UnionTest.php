<?php
declare(strict_types=1);

namespace Psalm\Tests\Type;

use InvalidArgumentException;
use Psalm\Tests\TestCase;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Union;

final class UnionTest extends TestCase
{

    public function testWillDetectSingleLiteralFloat(): void
    {
        $literalFloat = new TLiteralFloat(1.0);
        $union = new Union([$literalFloat]);

        self::assertTrue($union->isSingleFloatLiteral());
        self::assertTrue($union->hasLiteralFloat());
        self::assertSame($literalFloat, $union->getSingleFloatLiteral());
    }

    public function testWillThrowInvalidArgumentExceptionWhenSingleFloatLiteralIsRequestedButNoneExists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $union = new Union([new TFloat()]);
        $union->getSingleFloatLiteral();
    }
}
