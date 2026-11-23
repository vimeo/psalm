<?php

namespace Psalm\Tests\Fixtures\ExtraFiles\Src;

use Psalm\Tests\Fixtures\ExtraFiles\Dependency\ExtraFilesClass;
use Psalm\Tests\Fixtures\ExtraFiles\Dependency\ExtraFilesInterface;

class ConsumesExtraFiles
{
    public function consume(): string
    {
        $dependency = new ExtraFilesClass();

        return $this->describe($dependency);
    }

    private function describe(ExtraFilesInterface $dependency): string
    {
        return $dependency->extraFilesMethod();
    }
}
