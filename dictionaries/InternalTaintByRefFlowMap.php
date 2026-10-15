<?php

declare(strict_types=1);

/**
 * The builtin functions only declared by the call map that fill a by-reference parameter with data given to some of
 * their other parameters: by-reference parameter => the parameters whose taints flow into it. A parameter given as a
 * key flows through the path its value names, as in InternalTaintFlowMap.php: what builds an array out of a string
 * goes through `unserialization`.
 *
 * @var array<lowercase-string, non-empty-array<non-empty-string, non-empty-array<int|non-empty-string, non-empty-string>>>
 */
return [
    'mb_ereg' => ['matches' => ['string']],
    'mb_eregi' => ['matches' => ['string']],
    'mb_parse_str' => ['result' => ['string' => 'unserialization']],
    'openssl_open' => ['output' => ['data']],
    'openssl_private_decrypt' => ['decrypted_data' => ['data']],
    'openssl_public_decrypt' => ['decrypted_data' => ['data']],
    'parse_str' => ['result' => ['string' => 'unserialization']],
    'preg_match' => ['matches' => ['subject']],
    'preg_match_all' => ['matches' => ['subject']],
    'sscanf' => ['vars' => ['string']],
    'xml_parse_into_struct' => ['values' => ['data'], 'index' => ['data']],
];
