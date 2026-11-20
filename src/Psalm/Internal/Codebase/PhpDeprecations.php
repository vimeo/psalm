<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use function in_array;
use function intdiv;
use function str_starts_with;
use function strtolower;
use function trim;

/**
 * The builtin functions, methods and constants a PHP version deprecates, with what replaces them: using one is
 * reported whatever PHP version is analysed, as code using it breaks once PHP removes it
 *
 * @internal
 * @psalm-immutable
 */
final class PhpDeprecations
{
    private const MB_REGEX = 'the preg_* functions with the u modifier';

    private const HASH = 'the hash_*() functions';

    private const MHASH_CONSTANTS = [
        'MHASH_CRC32', 'MHASH_MD5', 'MHASH_SHA1', 'MHASH_HAVAL256', 'MHASH_RIPEMD160', 'MHASH_TIGER', 'MHASH_GOST',
        'MHASH_CRC32B', 'MHASH_HAVAL224', 'MHASH_HAVAL192', 'MHASH_HAVAL160', 'MHASH_HAVAL128', 'MHASH_TIGER128',
        'MHASH_TIGER160', 'MHASH_MD4', 'MHASH_SHA256', 'MHASH_ADLER32', 'MHASH_SHA224', 'MHASH_SHA512', 'MHASH_SHA384',
        'MHASH_WHIRLPOOL', 'MHASH_RIPEMD128', 'MHASH_RIPEMD256', 'MHASH_RIPEMD320', 'MHASH_SNEFRU256', 'MHASH_MD2',
        'MHASH_FNV132', 'MHASH_FNV1A32', 'MHASH_FNV164', 'MHASH_FNV1A64', 'MHASH_JOAAT', 'MHASH_CRC32C',
        'MHASH_MURMUR3A', 'MHASH_MURMUR3C', 'MHASH_MURMUR3F', 'MHASH_XXH32', 'MHASH_XXH64', 'MHASH_XXH3',
        'MHASH_XXH128',
    ];

    private const MYSQLI_REFRESH_CONSTANTS = [
        'MYSQLI_REFRESH_GRANT', 'MYSQLI_REFRESH_LOG', 'MYSQLI_REFRESH_TABLES', 'MYSQLI_REFRESH_HOSTS',
        'MYSQLI_REFRESH_STATUS', 'MYSQLI_REFRESH_THREADS', 'MYSQLI_REFRESH_REPLICA', 'MYSQLI_REFRESH_SLAVE',
        'MYSQLI_REFRESH_MASTER', 'MYSQLI_REFRESH_BACKUP_LOG',
    ];

    /** @var array<string, string> the PDO constants of a driver, as of PHP 8.5 the constants of its PDO subclass */
    private const PDO_DRIVER_CONSTANTS = [
        'DBLIB_ATTR_CONNECTION_TIMEOUT' => 'Pdo\\Dblib::ATTR_CONNECTION_TIMEOUT',
        'DBLIB_ATTR_QUERY_TIMEOUT' => 'Pdo\\Dblib::ATTR_QUERY_TIMEOUT',
        'DBLIB_ATTR_STRINGIFY_UNIQUEIDENTIFIER' => 'Pdo\\Dblib::ATTR_STRINGIFY_UNIQUEIDENTIFIER',
        'DBLIB_ATTR_VERSION' => 'Pdo\\Dblib::ATTR_VERSION',
        'DBLIB_ATTR_TDS_VERSION' => 'Pdo\\Dblib::ATTR_TDS_VERSION',
        'DBLIB_ATTR_SKIP_EMPTY_ROWSETS' => 'Pdo\\Dblib::ATTR_SKIP_EMPTY_ROWSETS',
        'DBLIB_ATTR_DATETIME_CONVERT' => 'Pdo\\Dblib::ATTR_DATETIME_CONVERT',
        'FB_ATTR_DATE_FORMAT' => 'Pdo\\Firebird::ATTR_DATE_FORMAT',
        'FB_ATTR_TIME_FORMAT' => 'Pdo\\Firebird::ATTR_TIME_FORMAT',
        'FB_ATTR_TIMESTAMP_FORMAT' => 'Pdo\\Firebird::ATTR_TIMESTAMP_FORMAT',
        'MYSQL_ATTR_USE_BUFFERED_QUERY' => 'Pdo\\Mysql::ATTR_USE_BUFFERED_QUERY',
        'MYSQL_ATTR_LOCAL_INFILE' => 'Pdo\\Mysql::ATTR_LOCAL_INFILE',
        'MYSQL_ATTR_LOCAL_INFILE_DIRECTORY' => 'Pdo\\Mysql::ATTR_LOCAL_INFILE_DIRECTORY',
        'MYSQL_ATTR_INIT_COMMAND' => 'Pdo\\Mysql::ATTR_INIT_COMMAND',
        'MYSQL_ATTR_MAX_BUFFER_SIZE' => 'Pdo\\Mysql::ATTR_MAX_BUFFER_SIZE',
        'MYSQL_ATTR_READ_DEFAULT_FILE' => 'Pdo\\Mysql::ATTR_READ_DEFAULT_FILE',
        'MYSQL_ATTR_READ_DEFAULT_GROUP' => 'Pdo\\Mysql::ATTR_READ_DEFAULT_GROUP',
        'MYSQL_ATTR_COMPRESS' => 'Pdo\\Mysql::ATTR_COMPRESS',
        'MYSQL_ATTR_DIRECT_QUERY' => 'Pdo\\Mysql::ATTR_DIRECT_QUERY',
        'MYSQL_ATTR_FOUND_ROWS' => 'Pdo\\Mysql::ATTR_FOUND_ROWS',
        'MYSQL_ATTR_IGNORE_SPACE' => 'Pdo\\Mysql::ATTR_IGNORE_SPACE',
        'MYSQL_ATTR_SSL_KEY' => 'Pdo\\Mysql::ATTR_SSL_KEY',
        'MYSQL_ATTR_SSL_CERT' => 'Pdo\\Mysql::ATTR_SSL_CERT',
        'MYSQL_ATTR_SSL_CA' => 'Pdo\\Mysql::ATTR_SSL_CA',
        'MYSQL_ATTR_SSL_CAPATH' => 'Pdo\\Mysql::ATTR_SSL_CAPATH',
        'MYSQL_ATTR_SSL_CIPHER' => 'Pdo\\Mysql::ATTR_SSL_CIPHER',
        'MYSQL_ATTR_SSL_VERIFY_SERVER_CERT' => 'Pdo\\Mysql::ATTR_SSL_VERIFY_SERVER_CERT',
        'MYSQL_ATTR_SERVER_PUBLIC_KEY' => 'Pdo\\Mysql::ATTR_SERVER_PUBLIC_KEY',
        'MYSQL_ATTR_MULTI_STATEMENTS' => 'Pdo\\Mysql::ATTR_MULTI_STATEMENTS',
        'ODBC_ATTR_USE_CURSOR_LIBRARY' => 'Pdo\\Odbc::ATTR_USE_CURSOR_LIBRARY',
        'ODBC_ATTR_ASSUME_UTF8' => 'Pdo\\Odbc::ATTR_ASSUME_UTF8',
        'ODBC_SQL_USE_IF_NEEDED' => 'Pdo\\Odbc::SQL_USE_IF_NEEDED',
        'ODBC_SQL_USE_DRIVER' => 'Pdo\\Odbc::SQL_USE_DRIVER',
        'ODBC_SQL_USE_ODBC' => 'Pdo\\Odbc::SQL_USE_ODBC',
        'PGSQL_ATTR_DISABLE_PREPARES' => 'Pdo\\Pgsql::ATTR_DISABLE_PREPARES',
        'SQLITE_ATTR_EXTENDED_RESULT_CODES' => 'Pdo\\Sqlite::ATTR_EXTENDED_RESULT_CODES',
        'SQLITE_ATTR_OPEN_FLAGS' => 'Pdo\\Sqlite::ATTR_OPEN_FLAGS',
        'SQLITE_ATTR_READONLY_STATEMENT' => 'Pdo\\Sqlite::ATTR_READONLY_STATEMENT',
        'SQLITE_DETERMINISTIC' => 'Pdo\\Sqlite::DETERMINISTIC',
        'SQLITE_OPEN_READONLY' => 'Pdo\\Sqlite::OPEN_READONLY',
        'SQLITE_OPEN_READWRITE' => 'Pdo\\Sqlite::OPEN_READWRITE',
        'SQLITE_OPEN_CREATE' => 'Pdo\\Sqlite::OPEN_CREATE',
    ];

    /** @var array<lowercase-string, string> the PDO methods of a driver, as of PHP 8.5 the methods of its PDO subclass */
    private const PDO_DRIVER_METHODS = [
        'pdo::pgsqlcopyfromarray' => 'Pdo\\Pgsql::copyFromArray()',
        'pdo::pgsqlcopyfromfile' => 'Pdo\\Pgsql::copyFromFile()',
        'pdo::pgsqlcopytoarray' => 'Pdo\\Pgsql::copyToArray()',
        'pdo::pgsqlcopytofile' => 'Pdo\\Pgsql::copyToFile()',
        'pdo::pgsqlgetnotify' => 'Pdo\\Pgsql::getNotify()',
        'pdo::pgsqlgetpid' => 'Pdo\\Pgsql::getPid()',
        'pdo::pgsqllobcreate' => 'Pdo\\Pgsql::lobCreate()',
        'pdo::pgsqllobopen' => 'Pdo\\Pgsql::lobOpen()',
        'pdo::pgsqllobunlink' => 'Pdo\\Pgsql::lobUnlink()',
        'pdo::sqlitecreateaggregate' => 'Pdo\\Sqlite::createAggregate()',
        'pdo::sqlitecreatecollation' => 'Pdo\\Sqlite::createCollation()',
        'pdo::sqlitecreatefunction' => 'Pdo\\Sqlite::createFunction()',
    ];

    /** @var array<lowercase-string, array{int, ?string}> function id => [PHP version id, replacement] */
    private const FUNCTIONS = [
        'mb_ereg' => [8_06_00, self::MB_REGEX],
        'mb_ereg_match' => [8_06_00, self::MB_REGEX],
        'mb_ereg_replace' => [8_06_00, self::MB_REGEX],
        'mb_ereg_replace_callback' => [8_06_00, self::MB_REGEX],
        'mb_ereg_search' => [8_06_00, self::MB_REGEX],
        'mb_ereg_search_getpos' => [8_06_00, self::MB_REGEX],
        'mb_ereg_search_getregs' => [8_06_00, self::MB_REGEX],
        'mb_ereg_search_init' => [8_06_00, self::MB_REGEX],
        'mb_ereg_search_pos' => [8_06_00, self::MB_REGEX],
        'mb_ereg_search_regs' => [8_06_00, self::MB_REGEX],
        'mb_ereg_search_setpos' => [8_06_00, self::MB_REGEX],
        'mb_eregi' => [8_06_00, self::MB_REGEX],
        'mb_eregi_replace' => [8_06_00, self::MB_REGEX],
        'mb_regex_encoding' => [8_06_00, self::MB_REGEX],
        'mb_regex_set_options' => [8_06_00, self::MB_REGEX],
        'mb_split' => [8_06_00, 'preg_split() with the u modifier'],
        'is_double' => [8_06_00, 'is_float()'],
        'is_long' => [8_06_00, 'is_int()'],
        'is_integer' => [8_06_00, 'is_int()'],
        'doubleval' => [8_06_00, 'floatval()'],
        'spl_object_hash' => [8_06_00, 'spl_object_id()'],
        'spl_classes' => [8_06_00, 'ReflectionExtension::getClassNames()'],
        'strcoll' => [8_06_00, 'Collator::compare()'],
        'metaphone' => [8_06_00, null],
        'mysqli_get_charset' => [8_06_00, 'mysqli_character_set_name()'],
        'mysqli_stmt_init' => [8_06_00, 'mysqli_prepare()'],
        'curl_close' => [8_05_00, null],
        'curl_share_close' => [8_05_00, null],
        'finfo_close' => [8_05_00, null],
        'imagedestroy' => [8_05_00, null],
        'xml_parser_free' => [8_05_00, null],
        'mysqli_execute' => [8_05_00, 'mysqli_stmt_execute()'],
        'socket_set_timeout' => [8_05_00, 'stream_set_timeout()'],
        'ldap_connect_wallet' => [8_05_00, null],
        'lcg_value' => [8_04_00, 'Random\\Randomizer::getFloat()'],
        'xml_set_object' => [8_04_00, 'handlers that are callables of the object'],
        'mysqli_ping' => [8_04_00, null],
        'mysqli_kill' => [8_04_00, 'a KILL query'],
        'mysqli_refresh' => [8_04_00, 'a FLUSH query'],
        'assert_options' => [8_03_00, null],
        'utf8_encode' => [8_02_00, 'mb_convert_encoding()'],
        'utf8_decode' => [8_02_00, 'mb_convert_encoding()'],
        'date_sunrise' => [8_01_00, 'date_sun_info()'],
        'date_sunset' => [8_01_00, 'date_sun_info()'],
        'strptime' => [8_01_00, 'date_parse_from_format() or IntlDateFormatter::parse()'],
        'strftime' => [8_01_00, 'date() or IntlDateFormatter::format()'],
        'gmstrftime' => [8_01_00, 'gmdate() or IntlDateFormatter::format()'],
        'mhash' => [8_01_00, self::HASH],
        'mhash_keygen_s2k' => [8_01_00, self::HASH],
        'mhash_count' => [8_01_00, self::HASH],
        'mhash_get_block_size' => [8_01_00, self::HASH],
        'mhash_get_hash_name' => [8_01_00, self::HASH],
        'odbc_result_all' => [8_01_00, null],
    ];

    /** @var array<lowercase-string, array{int, ?string}> declaring method id => [PHP version id, replacement] */
    private const METHODS = [
        'mysqli::get_charset' => [8_06_00, 'mysqli::character_set_name()'],
        'mysqli::stmt_init' => [8_06_00, 'mysqli::prepare()'],
        'arrayiterator::getflags' => [8_06_00, null],
        'arrayiterator::setflags' => [8_06_00, null],
        'arrayiterator::asort' => [8_06_00, null],
        'arrayiterator::ksort' => [8_06_00, null],
        'arrayiterator::uasort' => [8_06_00, null],
        'arrayiterator::uksort' => [8_06_00, null],
        'arrayiterator::natsort' => [8_06_00, null],
        'arrayiterator::natcasesort' => [8_06_00, null],
        'arrayiterator::serialize' => [8_06_00, null],
        'arrayiterator::unserialize' => [8_06_00, null],
        'splfileobject::fgetcsv' => [8_06_00, 'fgetcsv() on a stream'],
        'splfileobject::fputcsv' => [8_06_00, 'fputcsv() on a stream'],
        'splfileobject::setcsvcontrol' => [8_06_00, 'fgetcsv() or fputcsv() on a stream'],
        'splfileobject::getcsvcontrol' => [8_06_00, 'fgetcsv() or fputcsv() on a stream'],
        'splobjectstorage::contains' => [8_05_00, 'SplObjectStorage::offsetExists()'],
        'splobjectstorage::attach' => [8_05_00, 'SplObjectStorage::offsetSet()'],
        'splobjectstorage::detach' => [8_05_00, 'SplObjectStorage::offsetUnset()'],
        'reflectionproperty::setaccessible' => [8_05_00, null],
        'reflectionmethod::setaccessible' => [8_05_00, null],
        'splfixedarray::__wakeup' => [8_04_00, 'SplFixedArray::__unserialize()'],
        'mysqli::ping' => [8_04_00, null],
        'mysqli::kill' => [8_04_00, 'a KILL query'],
        'mysqli::refresh' => [8_04_00, 'a FLUSH query'],
        'splfileinfo::_bad_state_ex' => [8_02_00, null],
        'mysqli::init' => [8_01_00, 'mysqli::__construct()'],
    ];

    /** @var array<string, array{int, ?string}> constant name => [PHP version id, replacement] */
    private const CONSTANTS = [
        'SORT_LOCALE_STRING' => [8_06_00, 'Collator::sort()'],
        'MB_ONIGURUMA_VERSION' => [8_06_00, null],
        'DATE_RFC7231' => [8_05_00, null],
        'GSLC_SSL_NO_UATH' => [8_05_00, null],
        'GSLC_SSL_ONEWAY_UATH' => [8_05_00, null],
        'GSLC_SSL_TWOWAY_UATH' => [8_05_00, null],
        'CURLOPT_BINARYTRANSFER' => [8_04_00, null],
        'DOM_PHP_ERR' => [8_04_00, null],
        'E_STRICT' => [8_04_00, null],
        'SUNFUNCS_RET_TIMESTAMP' => [8_04_00, 'date_sun_info()'],
        'SUNFUNCS_RET_STRING' => [8_04_00, 'date_sun_info()'],
        'SUNFUNCS_RET_DOUBLE' => [8_04_00, 'date_sun_info()'],
        'MYSQLI_STORE_RESULT_COPY_DATA' => [8_04_00, null],
        'SOAP_FUNCTIONS_ALL' => [8_04_00, null],
        'SID' => [8_04_00, 'session_name() and session_id()'],
        'U_MULTIPLE_DECIMAL_SEPERATORS' => [8_03_00, 'U_MULTIPLE_DECIMAL_SEPARATORS'],
        'ASSERT_ACTIVE' => [8_03_00, null],
        'ASSERT_BAIL' => [8_03_00, null],
        'ASSERT_CALLBACK' => [8_03_00, null],
        'ASSERT_EXCEPTION' => [8_03_00, null],
        'ASSERT_WARNING' => [8_03_00, null],
        'MT_RAND_PHP' => [8_03_00, null],
        'FILTER_SANITIZE_STRING' => [8_01_00, 'htmlspecialchars()'],
        'FILTER_SANITIZE_STRIPPED' => [8_01_00, 'htmlspecialchars()'],
        'NIL' => [8_01_00, '0'],
        'FILE_BINARY' => [8_01_00, null],
        'FILE_TEXT' => [8_01_00, null],
    ];

    /** @var array<lowercase-string, array{int, ?string}> ini setting => [PHP version id, replacement] */
    private const INI_SETTINGS = [
        'report_memleaks' => [8_05_00, null],
        'intl.error_level' => [8_05_00, 'intl.use_exceptions'],
        'session.sid_length' => [8_04_00, null],
        'session.sid_bits_per_character' => [8_04_00, null],
        'session.use_only_cookies' => [8_04_00, null],
        'session.use_trans_sid' => [8_04_00, null],
        'session.trans_sid_tags' => [8_04_00, null],
        'session.trans_sid_hosts' => [8_04_00, null],
        'session.referer_check' => [8_04_00, null],
        'assert.active' => [8_03_00, 'zend.assertions'],
        'assert.bail' => [8_03_00, null],
        'assert.callback' => [8_03_00, null],
        'assert.exception' => [8_03_00, null],
        'assert.warning' => [8_03_00, null],
        'filter.default' => [8_01_00, null],
        'auto_detect_line_endings' => [8_01_00, null],
        'oci8.old_oci_close_semantics' => [8_01_00, null],
    ];

    /** @var array<lowercase-string, array{int, ?string}> mbstring encoding name or alias => [PHP version id, replacement] */
    private const MB_ENCODINGS = [
        'qprint' => [8_02_00, 'quoted_printable_encode()'],
        'quoted-printable' => [8_02_00, 'quoted_printable_encode()'],
        'base64' => [8_02_00, 'base64_encode()'],
        'uuencode' => [8_02_00, 'convert_uuencode()'],
        'html-entities' => [8_02_00, 'htmlspecialchars() or mb_encode_numericentity()'],
        'html' => [8_02_00, 'htmlspecialchars() or mb_encode_numericentity()'],
    ];

    /** @var array<string, array{int, ?string}> lowercase class name::constant name => [PHP version id, replacement] */
    private const CLASS_CONSTANTS = [
        'datetimeinterface::RFC7231' => [8_05_00, null],
        'numberformatter::TYPE_CURRENCY' => [8_03_00, 'NumberFormatter::formatCurrency() or parseCurrency()'],
        'ziparchive::FL_RECOMPRESS' => [8_03_00, null],
        'pdo::FETCH_SERIALIZE' => [8_01_00, null],
    ];

    /** @var array<lowercase-string, array{int, ?string}> declaring property id => [PHP version id, replacement] */
    private const PROPERTIES = [
        'domdocument::$actualencoding' => [8_04_00, null],
        'domdocument::$config' => [8_04_00, null],
        'domentity::$actualencoding' => [8_04_00, null],
        'domentity::$encoding' => [8_04_00, null],
        'domentity::$version' => [8_04_00, null],
        'mysqli_driver::$driver_version' => [8_01_00, 'PHP_VERSION_ID'],
    ];

    /**
     * @var array<lowercase-string, int> the keywords PHP adds, by the PHP version that deprecates them as names of
     * classes, interfaces, traits, enums, functions, constants and aliases
     */
    private const KEYWORDS = [
        'let' => 8_06_00,
        'is' => 8_06_00,
    ];

    /**
     * Why the builtin function $function_id is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getFunctionMessage(string $function_id): ?string
    {
        return self::getMessage('The function ' . $function_id, self::FUNCTIONS[strtolower($function_id)] ?? null);
    }

    /**
     * Why the builtin method $cased_method_id, declared as $declaring_method_id, is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getMethodMessage(string $declaring_method_id, string $cased_method_id): ?string
    {
        $declaring_method_id = strtolower($declaring_method_id);

        if (isset(self::PDO_DRIVER_METHODS[$declaring_method_id])) {
            return self::getDeprecatedSinceMessage(
                'The method ' . $cased_method_id,
                8_05_00,
                self::PDO_DRIVER_METHODS[$declaring_method_id],
            );
        }

        return self::getMessage('The method ' . $cased_method_id, self::METHODS[$declaring_method_id] ?? null);
    }

    /**
     * Why the constant $const_name of a builtin class is deprecated, if it is: $class_names are the class it is
     * fetched from, and its parents and interfaces
     *
     * @param list<string> $class_names
     * @psalm-pure
     */
    public static function getClassConstantMessage(array $class_names, string $const_name): ?string
    {
        foreach ($class_names as $class_name) {
            $class_name_lc = strtolower($class_name);

            if ($class_name_lc === 'pdo' && isset(self::PDO_DRIVER_CONSTANTS[$const_name])) {
                return self::getDeprecatedSinceMessage(
                    'The constant PDO::' . $const_name,
                    8_05_00,
                    self::PDO_DRIVER_CONSTANTS[$const_name],
                );
            }

            if ($class_name_lc === 'pdo' && str_starts_with($const_name, 'PGSQL_TRANSACTION_')) {
                return self::getDeprecatedSinceMessage('The constant PDO::' . $const_name, 8_05_00, null)
                    . ': PDO has no transaction state';
            }

            $deprecation = self::CLASS_CONSTANTS[$class_name_lc . '::' . $const_name] ?? null;

            if ($deprecation !== null) {
                return self::getMessage('The constant ' . $class_name . '::' . $const_name, $deprecation);
            }
        }

        return null;
    }

    /**
     * Why changing the ini setting $name is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getIniSettingMessage(string $name): ?string
    {
        return self::getMessage('Changing the ini setting ' . $name, self::INI_SETTINGS[strtolower($name)] ?? null);
    }

    /**
     * Why the mbstring encoding $encoding is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getMbEncodingMessage(string $encoding): ?string
    {
        return self::getMessage(
            'The mbstring encoding ' . $encoding,
            self::MB_ENCODINGS[strtolower(trim($encoding))] ?? null,
        );
    }

    /**
     * Why the builtin property $cased_property_id, declared as $declaring_property_id, is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getPropertyMessage(string $declaring_property_id, string $cased_property_id): ?string
    {
        return self::getMessage(
            'The property ' . $cased_property_id,
            self::PROPERTIES[strtolower($declaring_property_id)] ?? null,
        );
    }

    /**
     * Why the builtin constant $name is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getConstantMessage(string $name): ?string
    {
        if (in_array($name, self::MHASH_CONSTANTS, true)) {
            return self::getDeprecatedSinceMessage('The constant ' . $name, 8_05_00, self::HASH);
        }

        if (in_array($name, self::MYSQLI_REFRESH_CONSTANTS, true)) {
            return self::getDeprecatedSinceMessage('The constant ' . $name, 8_04_00, 'a FLUSH query');
        }

        return self::getMessage('The constant ' . $name, self::CONSTANTS[$name] ?? null);
    }

    /**
     * Why naming a class-like, when $declared, or a class alias $name is deprecated, if it is: $kind is `a class`,
     * `an interface`, ...
     *
     * @psalm-pure
     */
    public static function getClassLikeNameMessage(string $kind, string $name, bool $declared): ?string
    {
        if ($declared && $name === '_') {
            return self::getDeprecatedSinceMessage('Naming ' . $kind . ' _', 8_04_00, null);
        }

        return self::getKeywordNameMessage($kind, $name);
    }

    /**
     * Why naming a function $name is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getFunctionNameMessage(string $name): ?string
    {
        if (strtolower($name) === 'readonly') {
            return self::getDeprecatedSinceMessage('Naming a function readonly', 8_06_00, null);
        }

        return self::getKeywordNameMessage('a function', $name);
    }

    /**
     * Why naming a constant $name, with `const` or define(), is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getConstantNameMessage(string $name): ?string
    {
        if ($name === '_') {
            return self::getDeprecatedSinceMessage('Naming a constant _', 8_06_00, null);
        }

        return self::getKeywordNameMessage('a constant', $name);
    }

    /**
     * Why the alias $alias of a `use` declaration is deprecated, if it is
     *
     * @psalm-pure
     */
    public static function getUseAliasMessage(string $alias, bool $of_a_function): ?string
    {
        if ($alias === '_' && !$of_a_function) {
            return self::getDeprecatedSinceMessage('Naming an alias _', 8_06_00, null);
        }

        return self::getKeywordNameMessage('an alias', $alias);
    }

    /**
     * The message saying $subject is deprecated since a PHP version, and what replaces it
     *
     * @psalm-pure
     */
    public static function getDeprecatedSinceMessage(
        string $subject,
        int $since_php_version_id,
        ?string $replacement,
    ): string {
        return $subject . ' is deprecated since PHP ' . intdiv($since_php_version_id, 10_000) . '.'
            . intdiv($since_php_version_id % 10_000, 100)
            . ($replacement === null ? '' : ', use ' . $replacement . ' instead');
    }

    /**
     * @psalm-pure
     */
    private static function getKeywordNameMessage(string $kind, string $name): ?string
    {
        $name_lc = strtolower($name);
        $since = self::KEYWORDS[$name_lc] ?? null;

        return $since === null
            ? null
            : self::getDeprecatedSinceMessage('Naming ' . $kind . ' ' . $name, $since, null)
                . ', as ' . $name_lc . ' becomes a keyword';
    }

    /**
     * @param ?array{int, ?string} $deprecation
     * @psalm-pure
     */
    private static function getMessage(string $subject, ?array $deprecation): ?string
    {
        return $deprecation === null
            ? null
            : self::getDeprecatedSinceMessage($subject, $deprecation[0], $deprecation[1]);
    }
}
