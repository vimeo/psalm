<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Provider\FileStorageProvider;

final class FileStorageProviderTest extends TestCase
{
    public function testTheErrorForAMissingStorageNamesThePathAsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not get file storage for /project/src/C3.php');

        (new FileStorageProvider())->get('/project/src/C3.php');
    }
}
