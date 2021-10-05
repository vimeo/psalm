<?php

declare(strict_types=1);


namespace Psalm\Tests;


use Psalm\Context;

class SomeTest extends TestCase
{

    public function testStuff()
    {
        $this->addFile(
            'somefile.php',
            '<?php
                class A
                {
                }

                $reflected_class = new ReflectionClass(A::class);
                $reflected_class->getReflectionConstants(1);
                '
        );

        $this->analyzeFile('somefile.php', new Context());
    }
}
