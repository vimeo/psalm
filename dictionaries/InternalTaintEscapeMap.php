<?php

declare(strict_types=1);

use Psalm\Type\TaintKind;

/**
 * The builtin functions only declared by the call map whose return value can't carry some of the taints of what they
 * are given (see dictionaries/InternalTaintFlowMap.php), as with `@psalm-taint-escape`. The builtins declared by stubs
 * carry `@psalm-taint-escape` annotations in the stubs instead.
 *
 * @var array<lowercase-string, non-empty-list<key-of<TaintKind::TAINT_NAMES>>>
 */
return [
    // the strings it is given are length-prefixed: they can't change which values unserializing it creates
    'serialize' => ['unserialize'],
];
