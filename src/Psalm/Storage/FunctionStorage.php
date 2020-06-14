<?php
namespace Psalm\Storage;

use function array_map;
use function implode;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Type;

class FunctionStorage extends FunctionLikeStorage
{
    /** @var array<string, bool> */
    public $byref_uses = [];
}
