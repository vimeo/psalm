<?php

declare(strict_types=1);

use Psalm\Type\TaintKind;

/**
 * The builtin functions only declared by the call map whose return value can't carry some of the taints of what they
 * are given (see dictionaries/InternalTaintFlowMap.php), as with `@psalm-taint-escape`. The builtins declared by stubs
 * carry `@psalm-taint-escape` annotations in the stubs instead. `~secret_key` escapes every taint but secret_key.
 *
 * @var array<lowercase-string, non-empty-list<key-of<TaintKind::TAINT_NAMES>|'~secret_key'>>
 */
return [
    // a hash can't be read back: what it is computed from can only be guessed, if it is no secret
    'crypt' => ['~secret_key'],
    'hash' => ['~secret_key'],
    'hash_hkdf' => ['~secret_key'],
    'hash_hmac' => ['~secret_key'],
    'hash_hmac_file' => ['~secret_key'],
    'hash_pbkdf2' => ['~secret_key'],
    'md5' => ['~secret_key'],
    'sha1' => ['~secret_key'],
    // hexadecimal digits only
    'sodium_bin2hex' => ['input'],
    // the strings it is given are length-prefixed: they can't change which values unserializing it creates
    'serialize' => ['unserialize'],
];
