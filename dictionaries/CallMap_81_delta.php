<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 8.1 to php 8.0 (and vice versa)
 *
 * This file has three sections.
 * The 'added' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php 8.0
 * The 'removed' section contains the signatures that were removed in php 8.1
 * The 'changed' section contains functions for which the signature has changed for php 8.1.
 *     Each function in the 'changed' section has an 'old' and a 'new' section,
 *     representing the function as it was in PHP 8.0 and in PHP 8.1, respectively
 *
 * @see CallMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
  'added' => [
    'array_is_list' => ['bool', 'array'=>'array'],
  ],
  'changed' => [

  ],
  'removed' => [

  ],
];
