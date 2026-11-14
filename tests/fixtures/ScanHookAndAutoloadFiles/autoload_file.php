<?php

declare(strict_types=1);

use Psalm\Tests\fixtures\ScanHookAndAutoloadFiles\ReachableFromAutoloadFile;

// never called, so requiring this file doesn't load the class
static fn(): ReachableFromAutoloadFile => new ReachableFromAutoloadFile();
