<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use Amp\Serialization\NativeSerializer;
use Psalm\Internal\GzipSerializer;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function gzdeflate;
use function range;
use function str_repeat;

final class GzipSerializerTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $serializer = new GzipSerializer(new NativeSerializer());
        $data = ['a' => range(1, 500), 'b' => str_repeat('abc', 1000)];

        $this->assertSame($data, $serializer->unserialize($serializer->serialize($data)));
    }

    /** Level 6 takes as long as serializing the data again, for a few percent of size. */
    public function testCompressesAtTheFastestLevel(): void
    {
        $inner = new NativeSerializer();
        $data = ['a' => range(1, 5000), 'b' => str_repeat('abc', 1000)];

        $this->assertSame(
            gzdeflate($inner->serialize($data), 1),
            (new GzipSerializer($inner))->serialize($data),
        );
    }

    /** A cache written at the previous level stays readable, its directory is not invalidated */
    public function testReadsDataCompressedAtAnotherLevel(): void
    {
        $inner = new NativeSerializer();
        $data = ['a' => range(1, 5000), 'b' => str_repeat('abc', 1000)];
        $compressed = gzdeflate($inner->serialize($data), 6);
        $this->assertIsString($compressed);

        $this->assertSame($data, (new GzipSerializer($inner))->unserialize($compressed));
    }

    public function testConfiguredDeflateCompressorUsesTheFastestLevel(): void
    {
        $config = new TestConfig();
        $config->use_igbinary = false;
        $config->compressor = 'deflate';
        $data = ['a' => range(1, 5000), 'b' => str_repeat('abc', 1000)];

        $this->assertSame(
            gzdeflate((new NativeSerializer())->serialize($data), 1),
            $config->getCacheSerializer()->serialize($data),
        );
    }
}
