<?php

namespace Psalm\Tests\Fixtures\ExtraFiles\Dependency;

class ExtraFilesClass implements ExtraFilesInterface
{
    public function extraFilesMethod(): string
    {
        // Deliberately broken: extra files are only shallow scanned, so this must never be reported.
        return 5;
    }
}
