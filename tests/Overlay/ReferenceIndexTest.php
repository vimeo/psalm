<?php

declare(strict_types=1);

namespace Psalm\Tests\Overlay;

use PHPUnit\Framework\TestCase;
use Psalm\Internal\Overlay\ReferenceIndex;

use function assert;
use function file_put_contents;
use function filemtime;
use function sys_get_temp_dir;
use function tempnam;
use function touch;
use function unlink;

final class ReferenceIndexTest extends TestCase
{
    public function testAnEditThatKeepsTheSizeAndTheTimeIsAChange(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'PsalmStamp_');
        assert($file !== false);

        try {
            file_put_contents($file, 'AAAA');
            $stamp = ReferenceIndex::stamp($file);
            $mtime = filemtime($file);
            assert($mtime !== false);

            file_put_contents($file, 'BBBB');
            touch($file, $mtime);

            $this->assertFalse(ReferenceIndex::isUnchanged($file, $stamp));
        } finally {
            unlink($file);
        }
    }
}
