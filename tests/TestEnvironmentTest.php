<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

use function ini_get;

class TestEnvironmentTest extends PHPUnitTestCase
{
    public function testIniSettings(): void
    {
        $this->assertSame(
            '1',
            ini_get('zend.assertions'),
            'zend.assertions should be set to 1 to increase test strictness',
        );
    }
}
