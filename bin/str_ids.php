<?php

declare(strict_types=1);

// Interned strings which are not valid PHP identifiers: constant name => string.
// See bin/generate_str_ids.php.

return [
    'Decimal_Decimal' => 'Decimal\\Decimal',
    'decimal_decimal' => 'decimal\\decimal',
    'dollar_this' => '$this',
    'class_string_map' => 'class-string-map',
    'Psalm_Deprecated' => 'Psalm\\Deprecated',
    'JetBrains_PhpStorm_Deprecated' => 'JetBrains\\PhpStorm\\Deprecated',
    'Psalm_Internal' => 'Psalm\\Internal',
    'Psalm_Immutable' => 'Psalm\\Immutable',
    'JetBrains_PhpStorm_Immutable' => 'JetBrains\\PhpStorm\\Immutable',
    'Psalm_ExternalMutationFree' => 'Psalm\\ExternalMutationFree',
    'Psalm_Readonly' => 'Psalm\\Readonly',
    'ds_collection' => 'ds\\collection',
];
