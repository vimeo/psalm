<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use Amp\Serialization\SerializationException;
use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Fork\PhpSerializer;

use function ini_get;
use function ini_set;
use function str_repeat;

final class PhpSerializerTest extends TestCase
{
    private string $previous_max_depth;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->previous_max_depth = (string) ini_get('unserialize_max_depth');
        ini_set('unserialize_max_depth', '4096');
    }

    #[Override]
    protected function tearDown(): void
    {
        ini_set('unserialize_max_depth', $this->previous_max_depth);

        parent::tearDown();
    }

    private static function nestedArray(int $depth): string
    {
        return str_repeat('a:1:{i:0;', $depth) . 'i:1;' . str_repeat('}', $depth);
    }

    public function testUnserializesDeeperThanTheDefaultLimit(): void
    {
        self::assertIsArray((new PhpSerializer())->unserialize(self::nestedArray(PhpSerializer::MINIMUM_MAX_DEPTH)));
    }

    public function testRejectsPayloadsBeyondTheLimitInsteadOfCrashing(): void
    {
        $this->expectException(SerializationException::class);

        // deep enough to exhaust the call stack if unserialize() did not stop at the limit
        (new PhpSerializer())->unserialize(self::nestedArray(200_000));
    }

    public function testRespectsALargerConfiguredLimit(): void
    {
        ini_set('unserialize_max_depth', '20000');

        self::assertSame(20_000, PhpSerializer::getMaxDepth());
        self::assertIsArray((new PhpSerializer())->unserialize(self::nestedArray(15_000)));
    }
}
