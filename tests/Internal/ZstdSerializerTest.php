<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use Amp\Serialization\NativeSerializer;
use Amp\Serialization\SerializationException;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Psalm\Internal\ZstdSerializer;
use Psalm\Tests\TestCase;

use function range;
use function str_repeat;

#[RequiresPhpExtension('zstd', '>=0.15.0')]
final class ZstdSerializerTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $serializer = new ZstdSerializer(new NativeSerializer());
        $data = ['a' => range(1, 500), 'b' => str_repeat('abc', 1000)];

        $this->assertSame($data, $serializer->unserialize($serializer->serialize($data)));
    }

    public function testDictionaryIsRequiredToDecompress(): void
    {
        $dictionary = str_repeat('a shared prefix of the records ', 200);
        $with = (new ZstdSerializer(new NativeSerializer()))->withDictionary($dictionary);
        $data = $with->compress($dictionary . str_repeat('xyz', 100));

        $this->assertSame($dictionary . str_repeat('xyz', 100), $with->uncompress($data));

        $this->expectException(SerializationException::class);
        $this->withoutWarnings(static fn(): string => (new ZstdSerializer(new NativeSerializer()))->uncompress($data));
    }

    public function testUncompressingGarbageThrows(): void
    {
        $this->expectException(SerializationException::class);

        $this->withoutWarnings(
            static fn(): mixed => (new ZstdSerializer(new NativeSerializer()))->unserialize('not a zstd frame'),
        );
    }
}
