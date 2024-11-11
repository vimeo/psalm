<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 8.2 to php 8.1 (and vice versa)
 *
 * This file has three sections.
 * The 'added' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php 8.1
 * The 'removed' section contains the signatures that were removed in php 8.2
 * The 'changed' section contains functions for which the signature has changed for php 8.2.
 *     Each function in the 'changed' section has an 'old' and a 'new' section,
 *     representing the function as it was in PHP 8.1 and in PHP 8.2, respectively
 *
 * @see CallMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
  'added' => [
    'mysqli_execute_query' => ['mysqli_result|bool', 'mysql'=>'mysqli', 'query'=>'non-empty-string', 'params='=>'list<mixed>|null'],
    'mysqli::execute_query' => ['mysqli_result|bool', 'query'=>'non-empty-string', 'params='=>'list<mixed>|null'],
    'openssl_cipher_key_length' => ['positive-int|false', 'cipher_algo'=>'non-empty-string'],
    'curl_upkeep' => ['bool', 'handle'=>'CurlHandle'],
    'imap_is_open' => ['bool', 'imap'=>'IMAP\Connection'],
    'ini_parse_quantity' => ['int', 'shorthand'=>'non-empty-string'],
    'libxml_get_external_entity_loader' => ['(callable(string,string,array{directory:?string,intSubName:?string,extSubURI:?string,extSubSystem:?string}):(resource|string|null))|null'],
    'memory_reset_peak_usage' => ['void'],
    'sodium_crypto_stream_xchacha20_xor_ic' => ['string', 'message'=>'string', 'nonce'=>'non-empty-string', 'counter'=>'int', 'key'=>'non-empty-string'],
    'ZipArchive::clearError' => ['void'],
    'ZipArchive::getStreamIndex' => ['resource|false', 'index'=>'int', 'flags='=>'int'],
    'ZipArchive::getStreamName' => ['resource|false', 'name'=>'string', 'flags='=>'int'],
    'DateTimeInterface::__serialize' => ['array'],
    'DateTimeInterface::__unserialize' => ['void', 'data'=>'array'],
  ],

  'changed' => [
    'curl_errno' => [
      'old' => ['0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|52|53|54|55|56|57|58|59|60|61|62|63|64|77|79|90', 'handle'=>'CurlHandle'],
      'new' => ['0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|52|53|54|55|56|57|58|59|60|61|62|63|64|77|79|90|97', 'handle'=>'CurlHandle'],
    ],
    'curl_multi_info_read' => [
        'old' => ['array{msg: 1, result: 0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|52|53|54|55|56|57|58|59|60|61|62|63|64|77|79|90, handle: CurlHandle}|false', 'multi_handle'=>'CurlMultiHandle', '&w_queued_messages='=>'int<0, max>'],
        'new' => ['array{msg: 1, result: 0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|52|53|54|55|56|57|58|59|60|61|62|63|64|77|79|90|97, handle: CurlHandle}|false', 'multi_handle'=>'CurlMultiHandle', '&w_queued_messages='=>'int<0, max>'],
    ],
    'curl_pause' => [
        'old' => ['0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|52|53|54|55|56|57|58|59|60|61|62|63|64|77|79|90', 'handle'=>'CurlHandle', 'flags'=>'0|1|4|5'],
        'new' => ['0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|52|53|54|55|56|57|58|59|60|61|62|63|64|77|79|90|97', 'handle'=>'CurlHandle', 'flags'=>'0|1|4|5'],
    ],
    'curl_share_strerror' => [
        'old' => ['?string', 'error_code'=>'0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|52|53|54|55|56|57|58|59|60|61|62|63|64|77|79|90'],
        'new' => ['?string', 'error_code'=>'0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|52|53|54|55|56|57|58|59|60|61|62|63|64|77|79|90|97'],
    ],
    'curl_share_errno' => [
        'old' => ['0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|52|53|54|55|56|57|58|59|60|61|62|63|64|77|79|90', 'share_handle'=>'CurlShareHandle'],
        'new' => ['0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|52|53|54|55|56|57|58|59|60|61|62|63|64|77|79|90|97', 'share_handle'=>'CurlShareHandle'],
    ],
    'curl_strerror' => [
        'old' => ['?string', 'error_code'=>'0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|52|53|54|55|56|57|58|59|60|61|62|63|64|77|79|90'],
        'new' => ['?string', 'error_code'=>'0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|52|53|54|55|56|57|58|59|60|61|62|63|64|77|79|90|97'],
    ],
    'dba_open' => [
      'old' => ['resource', 'path'=>'string', 'mode'=>'string', 'handler='=>'string', '...handler_params='=>'string'],
      'new' => ['resource', 'path'=>'string', 'mode'=>'string', 'handler='=>'?string', 'permission='=>'int', 'map_size='=>'int', 'flags='=>'?int'],
    ],
    'dba_popen' => [
      'old' => ['resource', 'path'=>'string', 'mode'=>'string', 'handler='=>'string', '...handler_params='=>'string'],
      'new' => ['resource', 'path'=>'string', 'mode'=>'string', 'handler='=>'?string', 'permission='=>'int', 'map_size='=>'int', 'flags='=>'?int'],
    ],
    'iterator_count' => [
      'old' => ['0|positive-int', 'iterator'=>'Traversable'],
      'new' => ['0|positive-int', 'iterator'=>'Traversable|array'],
    ],
    'iterator_to_array' => [
      'old' => ['array', 'iterator'=>'Traversable', 'preserve_keys='=>'bool'],
      'new' => ['array', 'iterator'=>'Traversable|array', 'preserve_keys='=>'bool'],
    ],
    'str_split' => [
       'old' => ['non-empty-list<string>', 'string'=>'string', 'length='=>'positive-int'],
       'new' => ['list<non-empty-string>', 'string'=>'string', 'length='=>'positive-int'],
    ],
    'mb_get_info' => [
        'old' => ['array|string|int|false', 'type='=>'string'],
        'new' => ['array|string|int|false|null', 'type='=>'string'],
    ],
    'strcmp' => [
        'old' => ['int', 'string1' => 'string', 'string2' => 'string'],
        'new' => ['int<-1,1>', 'string1' => 'string', 'string2' => 'string'],
    ],
    'strcasecmp' => [
        'old' => ['int', 'string1' => 'string', 'string2' => 'string'],
        'new' => ['int<-1,1>', 'string1' => 'string', 'string2' => 'string'],
    ],
    'strnatcasecmp' => [
        'old' => ['int', 'string1' => 'string', 'string2' => 'string'],
        'new' => ['int<-1,1>', 'string1' => 'string', 'string2' => 'string'],
    ],
    'strnatcmp' => [
        'old' => ['int', 'string1' => 'string', 'string2' => 'string'],
        'new' => ['int<-1,1>', 'string1' => 'string', 'string2' => 'string'],
    ],
    'strncmp' => [
        'old' => ['int', 'string1'=>'string', 'string2'=>'string', 'length'=>'int'],
        'new' => ['int<-1,1>', 'string1' => 'string', 'string2' => 'string', 'length'=>'positive-int|0'],
    ],
    'strncasecmp' => [
        'old' => ['int', 'string1'=>'string', 'string2'=>'string', 'length'=>'int'],
        'new' => ['int<-1,1>', 'string1' => 'string', 'string2' => 'string', 'length'=>'positive-int|0'],
    ],
  ],

  'removed' => [
  ],
];
