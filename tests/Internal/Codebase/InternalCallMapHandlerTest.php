<?php

namespace Psalm\Tests\Internal\Codebase;

use InvalidArgumentException;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\Reflection;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;
use Psalm\Type;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;

use function array_shift;
use function class_exists;
use function count;
use function explode;
use function function_exists;
use function in_array;
use function is_array;
use function is_int;
use function json_encode;
use function preg_match;
use function print_r;
use function strcmp;
use function strncmp;
use function strpos;
use function substr;
use function version_compare;

use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const PHP_VERSION;

class InternalCallMapHandlerTest extends TestCase
{
    /**
     * Specify a function name as value, or a function name as key and
     * an array containing the PHP versions in which to ignore this function as values.
     *
     * @var array<int|string, string|list<string>>
     */
    private static array $ignoredFunctions = [
        'apcu_entry',
        'array_multisort',
        'arrayiterator::asort',
        'arrayiterator::ksort',
        'arrayiterator::offsetexists',
        'arrayiterator::offsetget',
        'arrayiterator::offsetset',
        'arrayiterator::offsetunset',
        'arrayiterator::seek',
        'arrayiterator::setflags',
        'arrayiterator::uasort',
        'arrayiterator::uksort',
        'arrayiterator::unserialize',
        'arrayobject::__construct',
        'arrayobject::asort',
        'arrayobject::exchangearray',
        'arrayobject::ksort',
        'arrayobject::offsetexists',
        'arrayobject::offsetget',
        'arrayobject::offsetset',
        'arrayobject::offsetunset',
        'arrayobject::setiteratorclass',
        'arrayobject::uasort',
        'arrayobject::uksort',
        'arrayobject::unserialize',
        'bcdiv',
        'bcmod',
        'bcpowmod',
        'bzdecompress',
        'cachingiterator::offsetexists',
        'cachingiterator::offsetget',
        'cachingiterator::offsetset',
        'cachingiterator::offsetunset',
        'callbackfilteriterator::__construct',
        'closure::bind',
        'closure::bindto',
        'closure::call',
        'closure::fromcallable',
        'collator::asort',
        'collator::getattribute',
        'collator::setattribute',
        'collator::sort',
        'collator::sortwithsortkeys',
        'crypt',
        'curlfile::__construct',
        'curlfile::setmimetype',
        'curlfile::setpostfilename',
        'date_isodate_set',
        'datefmt_create' => ['8.0'],
        'dateinterval::__construct',
        'dateinterval::createfromdatestring',
        'datetime::createfromformat',
        'datetime::diff',
        'datetime::modify',
        'datetime::setisodate',
        'datetime::settime',
        'datetime::settimestamp',
        'datetimezone::gettransitions',
        'debug_zval_dump',
        'directoryiterator::__construct',
        'directoryiterator::getfileinfo',
        'directoryiterator::getpathinfo',
        'directoryiterator::openfile',
        'directoryiterator::seek',
        'directoryiterator::setfileclass',
        'directoryiterator::setinfoclass',
        'dns_get_mx',
        'domattr::insertbefore',
        'domattr::isdefaultnamespace',
        'domattr::issamenode',
        'domattr::lookupprefix',
        'domattr::removechild',
        'domattr::replacechild',
        'domcdatasection::__construct',
        'domcomment::__construct',
        'domdocument::createattribute',
        'domdocument::createattributens',
        'domdocument::createelement',
        'domdocument::createelementns',
        'domdocument::createtextnode',
        'domdocument::getelementbyid',
        'domdocument::getelementsbytagname',
        'domdocument::getelementsbytagnamens',
        'domdocument::importnode',
        'domdocument::registernodeclass',
        'domelement::__construct',
        'domelement::getattribute',
        'domelement::getattributenode',
        'domelement::getattributenodens',
        'domelement::getattributens',
        'domelement::getelementsbytagname',
        'domelement::getelementsbytagnamens',
        'domelement::hasattribute',
        'domelement::hasattributens',
        'domelement::removeattribute',
        'domelement::removeattributenode',
        'domelement::removeattributens',
        'domelement::setattribute',
        'domelement::setattributens',
        'domelement::setidattribute',
        'domelement::setidattributenode',
        'domelement::setidattributens',
        'domimplementation::createdocument',
        'domimplementation::createdocumenttype',
        'domnamednodemap::getnameditem',
        'domnamednodemap::getnameditemns',
        'domnode::appendchild',
        'domnode::c14n',
        'domnode::c14nfile',
        'domnode::insertbefore',
        'domnode::isdefaultnamespace',
        'domnode::issamenode',
        'domnode::lookupprefix',
        'domnode::removechild',
        'domnode::replacechild',
        'domprocessinginstruction::__construct',
        'domtext::__construct',
        'domxpath::__construct',
        'domxpath::evaluate',
        'domxpath::query',
        'domxpath::registernamespace',
        'domxpath::registerphpfunctions',
        'easter_date',
        'enchant_broker_describe',
        'enchant_broker_dict_exists',
        'enchant_broker_free',
        'enchant_broker_free_dict',
        'enchant_broker_get_dict_path',
        'enchant_broker_get_error',
        'enchant_broker_list_dicts',
        'enchant_broker_request_dict',
        'enchant_broker_request_pwl_dict',
        'enchant_broker_set_dict_path',
        'enchant_broker_set_ordering',
        'enchant_dict_add_to_personal',
        'enchant_dict_add_to_session',
        'enchant_dict_check',
        'enchant_dict_describe',
        'enchant_dict_get_error',
        'enchant_dict_is_in_session',
        'enchant_dict_quick_check',
        'enchant_dict_store_replacement',
        'enchant_dict_suggest',
        'fiber::start',
        'filesystemiterator::__construct',
        'filesystemiterator::getfileinfo',
        'filesystemiterator::getpathinfo',
        'filesystemiterator::openfile',
        'filesystemiterator::seek',
        'filesystemiterator::setfileclass',
        'filesystemiterator::setflags',
        'filesystemiterator::setinfoclass',
        'finfo::__construct',
        'finfo::buffer',
        'finfo::file',
        'finfo::set_flags',
        'generator::throw',
        'globiterator::__construct',
        'globiterator::getfileinfo',
        'globiterator::getpathinfo',
        'globiterator::openfile',
        'globiterator::seek',
        'globiterator::setfileclass',
        'globiterator::setflags',
        'globiterator::setinfoclass',
        'gmp_clrbit',
        'gmp_div',
        'gmp_setbit',
        'gnupg::adddecryptkey',
        'gnupg::addencryptkey',
        'gnupg::addsignkey',
        'gnupg::decrypt',
        'gnupg::decryptverify',
        'gnupg::encrypt',
        'gnupg::encryptsign',
        'gnupg::export',
        'gnupg::import',
        'gnupg::keyinfo',
        'gnupg::seterrormode',
        'gnupg::sign',
        'gnupg::verify',
        'gnupg_adddecryptkey',
        'gnupg_addencryptkey',
        'gnupg_addsignkey',
        'gnupg_cleardecryptkeys',
        'gnupg_clearencryptkeys',
        'gnupg_clearsignkeys',
        'gnupg_decrypt',
        'gnupg_decryptverify',
        'gnupg_encrypt',
        'gnupg_encryptsign',
        'gnupg_export',
        'gnupg_geterror',
        'gnupg_getprotocol',
        'gnupg_import',
        'gnupg_init',
        'gnupg_keyinfo',
        'gnupg_setarmor',
        'gnupg_seterrormode',
        'gnupg_setsignmode',
        'gnupg_sign',
        'gnupg_verify',
        'hash_hmac_file',
        'igbinary_unserialize',
        'imagefilledpolygon',
        'imagefilter',
        'imagegd',
        'imagegd2',
        'imageopenpolygon',
        'imagepolygon',
        'imagerotate',
        'imagesetinterpolation',
        'imagettfbbox',
        'imagettftext',
        'imagexbm',
        'imap_open',
        'imap_rfc822_write_address',
        'imap_sort',
        'inflate_add',
        'inflate_get_read_len',
        'inflate_get_status',
        'inotify_rm_watch',
        'intlbreakiterator::getlocale',
        'intlbreakiterator::getpartsiterator',
        'intlcal_from_date_time',
        'intlcal_get_weekend_transition',
        'intlcalendar::add',
        'intlcalendar::createinstance',
        'intlcalendar::fielddifference',
        'intlcalendar::fromdatetime',
        'intlcalendar::getkeywordvaluesforlocale',
        'intlcalendar::getlocale',
        'intlcalendar::getweekendtransition',
        'intlcalendar::isweekend',
        'intlcalendar::roll',
        'intlcalendar::setlenient',
        'intlcalendar::setminimaldaysinfirstweek',
        'intlcalendar::setrepeatedwalltimeoption',
        'intlcalendar::setskippedwalltimeoption',
        'intlcalendar::settime',
        'intlcalendar::settimezone',
        'intlchar::charage',
        'intlchar::chardigitvalue',
        'intlchar::chardirection',
        'intlchar::charfromname',
        'intlchar::charmirror',
        'intlchar::charname',
        'intlchar::chartype',
        'intlchar::chr',
        'intlchar::digit',
        'intlchar::enumcharnames',
        'intlchar::enumchartypes',
        'intlchar::foldcase',
        'intlchar::fordigit',
        'intlchar::getbidipairedbracket',
        'intlchar::getblockcode',
        'intlchar::getcombiningclass',
        'intlchar::getfc_nfkc_closure',
        'intlchar::getintpropertyvalue',
        'intlchar::getnumericvalue',
        'intlchar::getpropertyname',
        'intlchar::getpropertyvaluename',
        'intlchar::hasbinaryproperty',
        'intlchar::isalnum',
        'intlchar::isalpha',
        'intlchar::isbase',
        'intlchar::isblank',
        'intlchar::iscntrl',
        'intlchar::isdefined',
        'intlchar::isdigit',
        'intlchar::isgraph',
        'intlchar::isidignorable',
        'intlchar::isidpart',
        'intlchar::isidstart',
        'intlchar::isisocontrol',
        'intlchar::isjavaidpart',
        'intlchar::isjavaidstart',
        'intlchar::isjavaspacechar',
        'intlchar::islower',
        'intlchar::ismirrored',
        'intlchar::isprint',
        'intlchar::ispunct',
        'intlchar::isspace',
        'intlchar::istitle',
        'intlchar::isualphabetic',
        'intlchar::isulowercase',
        'intlchar::isupper',
        'intlchar::isuuppercase',
        'intlchar::isuwhitespace',
        'intlchar::iswhitespace',
        'intlchar::isxdigit',
        'intlchar::ord',
        'intlchar::tolower',
        'intlchar::totitle',
        'intlchar::toupper',
        'intlcodepointbreakiterator::following',
        'intlcodepointbreakiterator::getlocale',
        'intlcodepointbreakiterator::getpartsiterator',
        'intlcodepointbreakiterator::isboundary',
        'intlcodepointbreakiterator::next',
        'intlcodepointbreakiterator::preceding',
        'intlexception::__construct',
        'intlgregcal_create_instance',
        'intlgregcal_is_leap_year',
        'intlgregoriancalendar::__construct',
        'intlgregoriancalendar::add',
        'intlgregoriancalendar::createinstance',
        'intlgregoriancalendar::fielddifference',
        'intlgregoriancalendar::fromdatetime',
        'intlgregoriancalendar::getkeywordvaluesforlocale',
        'intlgregoriancalendar::getlocale',
        'intlgregoriancalendar::getweekendtransition',
        'intlgregoriancalendar::isweekend',
        'intlgregoriancalendar::roll',
        'intlgregoriancalendar::setgregorianchange',
        'intlgregoriancalendar::setlenient',
        'intlgregoriancalendar::setminimaldaysinfirstweek',
        'intlgregoriancalendar::setrepeatedwalltimeoption',
        'intlgregoriancalendar::setskippedwalltimeoption',
        'intlgregoriancalendar::settime',
        'intlgregoriancalendar::settimezone',
        'intlrulebasedbreakiterator::__construct',
        'intlrulebasedbreakiterator::getlocale',
        'intlrulebasedbreakiterator::getpartsiterator',
        'intltimezone::countequivalentids',
        'intltimezone::createtimezone',
        'intltimezone::createtimezoneidenumeration',
        'intltimezone::fromdatetimezone',
        'intltimezone::getcanonicalid',
        'intltimezone::getdisplayname',
        'intltimezone::getequivalentid',
        'intltimezone::getidforwindowsid',
        'intltimezone::getoffset',
        'intltimezone::getregion',
        'intltimezone::getwindowsid',
        'intltimezone::hassamerules',
        'intltz_create_enumeration',
        'intltz_get_canonical_id',
        'intltz_get_display_name',
        'iteratoriterator::__construct',
        'jsonexception::__construct',
        'ldap_compare' => ['8.0'],
        'ldap_delete' => ['8.0'],
        'ldap_get_option' => ['8.0'],
        'ldap_list' => ['8.0'],
        'ldap_mod_add' => ['8.0'],
        'ldap_mod_del' => ['8.0'],
        'ldap_mod_replace' => ['8.0'],
        'ldap_modify' => ['8.0'],
        'ldap_modify_batch' => ['8.0'],
        'ldap_next_entry' => ['8.0'],
        'ldap_parse_reference' => ['8.0'],
        'ldap_read' => ['8.0'],
        'ldap_rename' => ['8.0'],
        'ldap_search' => ['8.0'],
        'limititerator::__construct',
        'limititerator::seek',
        'locale::filtermatches',
        'locale::getdisplaylanguage',
        'locale::getdisplayname',
        'locale::getdisplayregion',
        'locale::getdisplayscript',
        'locale::getdisplayvariant',
        'long2ip',
        'lzf_compress',
        'lzf_decompress',
        'mail',
        'mailparse_msg_extract_part',
        'mailparse_msg_extract_part_file',
        'mailparse_msg_extract_whole_part_file',
        'mailparse_msg_free',
        'mailparse_msg_get_part',
        'mailparse_msg_get_part_data',
        'mailparse_msg_get_structure',
        'mailparse_msg_parse',
        'mailparse_stream_encode',
        'memcache::add',
        'memcache::addserver',
        'memcache::append',
        'memcache::cas',
        'memcache::connect',
        'memcache::decrement',
        'memcache::delete',
        'memcache::findserver',
        'memcache::flush',
        'memcache::getserverstatus',
        'memcache::increment',
        'memcache::pconnect',
        'memcache::prepend',
        'memcache::replace',
        'memcache::set',
        'memcache::setfailurecallback',
        'memcache::setserverparams',
        'memcache_add',
        'memcache_add_server',
        'memcache_append',
        'memcache_cas',
        'memcache_close',
        'memcache_connect',
        'memcache_decrement',
        'memcache_delete',
        'memcache_flush',
        'memcache_get_extended_stats',
        'memcache_get_server_status',
        'memcache_get_stats',
        'memcache_get_version',
        'memcache_increment',
        'memcache_pconnect',
        'memcache_prepend',
        'memcache_replace',
        'memcache_set',
        'memcache_set_compress_threshold',
        'memcache_set_failure_callback',
        'memcache_set_server_params',
        'memcachepool::add',
        'memcachepool::addserver',
        'memcachepool::append',
        'memcachepool::cas',
        'memcachepool::connect',
        'memcachepool::decrement',
        'memcachepool::delete',
        'memcachepool::findserver',
        'memcachepool::flush',
        'memcachepool::get',
        'memcachepool::getserverstatus',
        'memcachepool::increment',
        'memcachepool::prepend',
        'memcachepool::replace',
        'memcachepool::set',
        'memcachepool::setcompressthreshold',
        'memcachepool::setfailurecallback',
        'memcachepool::setserverparams',
        'messageformatter::format',
        'messageformatter::formatmessage',
        'messageformatter::parse',
        'messageformatter::parsemessage',
        'mongodb\bson\binary::__construct',
        'msg_receive',
        'msg_remove_queue',
        'msg_send',
        'msg_set_queue',
        'msg_stat_queue',
        'multipleiterator::attachiterator',
        'mysqli::poll',
        'mysqli_poll',
        'mysqli_real_connect',
        'mysqli_stmt::__construct',
        'mysqli_stmt::bind_param',
        'mysqli_stmt_bind_param',
        'normalizer::getrawdecomposition',
        'normalizer::isnormalized',
        'normalizer::normalize',
        'normalizer_get_raw_decomposition',
        'numberformatter::formatcurrency',
        'numberformatter::getattribute',
        'numberformatter::getsymbol',
        'numberformatter::gettextattribute',
        'numberformatter::parse',
        'numberformatter::parsecurrency',
        'numberformatter::setattribute',
        'numberformatter::setsymbol',
        'numberformatter::settextattribute',
        'oauth::fetch',
        'oauth::getaccesstoken',
        'oauth::setcapath',
        'oauth::settimeout',
        'oauth::settimestamp',
        'oauth_get_sbs',
        'oauthprovider::consumerhandler',
        'oauthprovider::isrequesttokenendpoint',
        'oauthprovider::timestampnoncehandler',
        'oauthprovider::tokenhandler',
        'oci_collection_append',
        'oci_collection_assign',
        'oci_collection_element_assign',
        'oci_collection_element_get',
        'oci_collection_max',
        'oci_collection_size',
        'oci_collection_trim',
        'oci_fetch_object',
        'oci_field_is_null',
        'oci_field_name',
        'oci_field_precision',
        'oci_field_scale',
        'oci_field_size',
        'oci_field_type',
        'oci_field_type_raw',
        'oci_free_collection',
        'oci_free_descriptor',
        'oci_lob_append',
        'oci_lob_eof',
        'oci_lob_erase',
        'oci_lob_export',
        'oci_lob_flush',
        'oci_lob_import',
        'oci_lob_load',
        'oci_lob_read',
        'oci_lob_rewind',
        'oci_lob_save',
        'oci_lob_seek',
        'oci_lob_size',
        'oci_lob_tell',
        'oci_lob_truncate',
        'oci_lob_write',
        'oci_register_taf_callback',
        'oci_result',
        'ocigetbufferinglob',
        'ocisetbufferinglob',
        'odbc_procedurecolumns',
        'odbc_procedures',
        'odbc_result',
        'openssl_pkcs7_read',
        'pdo::__construct',
        'pdo::exec',
        'pdo::prepare',
        'pdo::quote',
        'pdostatement::bindcolumn',
        'pdostatement::bindparam',
        'pdostatement::fetchobject',
        'pdostatement::getattribute',
        'pg_exec',
        'pg_fetch_all',
        'pg_get_notify',
        'pg_get_result',
        'pg_pconnect',
        'pg_select',
        'pg_send_execute',
        'phar::__construct',
        'phar::addemptydir',
        'phar::addfile',
        'phar::addfromstring',
        'phar::buildfromdirectory',
        'phar::buildfromiterator',
        'phar::cancompress',
        'phar::copy',
        'phar::count',
        'phar::createdefaultstub',
        'phar::delete',
        'phar::extractto',
        'phar::mapphar',
        'phar::mount',
        'phar::mungserver',
        'phar::offsetexists',
        'phar::offsetget',
        'phar::offsetset',
        'phar::offsetunset',
        'phar::running',
        'phar::setdefaultstub',
        'phar::setsignaturealgorithm',
        'phar::unlinkarchive',
        'phar::webphar',
        'phardata::__construct',
        'phardata::addemptydir',
        'phardata::addfile',
        'phardata::addfromstring',
        'phardata::buildfromdirectory',
        'phardata::buildfromiterator',
        'phardata::copy',
        'phardata::delete',
        'phardata::extractto',
        'phardata::offsetexists',
        'phardata::offsetget',
        'phardata::offsetset',
        'phardata::offsetunset',
        'phardata::setdefaultstub',
        'phardata::setsignaturealgorithm',
        'pharfileinfo::__construct',
        'pharfileinfo::chmod',
        'pharfileinfo::iscompressed',
        'preg_filter',
        'preg_replace_callback_array',
        'recursivearrayiterator::asort',
        'recursivearrayiterator::ksort',
        'recursivearrayiterator::offsetexists',
        'recursivearrayiterator::offsetget',
        'recursivearrayiterator::offsetset',
        'recursivearrayiterator::offsetunset',
        'recursivearrayiterator::seek',
        'recursivearrayiterator::setflags',
        'recursivearrayiterator::uasort',
        'recursivearrayiterator::uksort',
        'recursivearrayiterator::unserialize',
        'recursivecachingiterator::__construct',
        'recursivecachingiterator::offsetexists',
        'recursivecachingiterator::offsetget',
        'recursivecachingiterator::offsetset',
        'recursivecachingiterator::offsetunset',
        'recursivecallbackfilteriterator::__construct',
        'recursivedirectoryiterator::__construct',
        'recursivedirectoryiterator::getfileinfo',
        'recursivedirectoryiterator::getpathinfo',
        'recursivedirectoryiterator::haschildren',
        'recursivedirectoryiterator::openfile',
        'recursivedirectoryiterator::seek',
        'recursivedirectoryiterator::setfileclass',
        'recursivedirectoryiterator::setflags',
        'recursivedirectoryiterator::setinfoclass',
        'recursiveiteratoriterator::__construct',
        'recursiveiteratoriterator::setmaxdepth',
        'recursiveregexiterator::__construct',
        'recursiveregexiterator::setflags',
        'recursiveregexiterator::setmode',
        'recursiveregexiterator::setpregflags',
        'recursivetreeiterator::__construct',
        'recursivetreeiterator::setmaxdepth',
        'recursivetreeiterator::setpostfix',
        'recursivetreeiterator::setprefixpart',
        'reflectionclass::__construct',
        'reflectionclass::implementsinterface',
        'reflectionclassconstant::__construct',
        'reflectionfunction::__construct',
        'reflectiongenerator::__construct',
        'reflectionmethod::setaccessible',
        'reflectionobject::__construct',
        'reflectionobject::getconstants',
        'reflectionobject::getreflectionconstants',
        'reflectionobject::implementsinterface',
        'reflectionparameter::__construct',
        'reflectionproperty::__construct',
        'reflectionproperty::setaccessible',
        'regexiterator::__construct',
        'regexiterator::setflags',
        'regexiterator::setmode',
        'regexiterator::setpregflags',
        'resourcebundle::__construct',
        'resourcebundle::create',
        'resourcebundle::getlocales',
        'sapi_windows_cp_get',
        'sem_acquire',
        'sem_get',
        'sem_release',
        'sem_remove',
        'sessionhandler::gc',
        'sessionhandler::open',
        'shm_detach',
        'shm_get_var',
        'shm_has_var',
        'shm_put_var',
        'shm_remove',
        'shm_remove_var',
        'simplexmlelement::__construct',
        'simplexmlelement::addattribute',
        'simplexmlelement::addchild',
        'simplexmlelement::attributes',
        'simplexmlelement::children',
        'simplexmlelement::getdocnamespaces',
        'simplexmlelement::registerxpathnamespace',
        'simplexmlelement::xpath',
        'snmp::set',
        'snmp_set_enum_print',
        'snmp_set_valueretrieval',
        'snmpset',
        'socket_addrinfo_lookup',
        'socket_bind',
        'socket_cmsg_space',
        'socket_connect',
        'socket_create_pair',
        'socket_get_option',
        'socket_getopt',
        'socket_getpeername',
        'socket_getsockname',
        'socket_read',
        'socket_recv',
        'socket_recvfrom',
        'socket_recvmsg',
        'socket_select',
        'socket_send',
        'socket_sendmsg',
        'socket_sendto',
        'socket_set_blocking',
        'socket_set_option',
        'socket_setopt',
        'socket_shutdown',
        'socket_strerror',
        'sodium_crypto_generichash',
        'sodium_crypto_generichash_final',
        'sodium_crypto_generichash_init',
        'sodium_crypto_generichash_update',
        'sodium_crypto_kx_client_session_keys',
        'sodium_crypto_secretstream_xchacha20poly1305_rekey',
        'spldoublylinkedlist::add',
        'spldoublylinkedlist::offsetset',
        'spldoublylinkedlist::setiteratormode',
        'spldoublylinkedlist::unserialize',
        'splfileinfo::__construct',
        'splfileinfo::getfileinfo',
        'splfileinfo::getpathinfo',
        'splfileinfo::openfile',
        'splfileinfo::setfileclass',
        'splfileinfo::setinfoclass',
        'splfileobject::__construct',
        'splfileobject::fgetcsv',
        'splfileobject::flock',
        'splfileobject::fputcsv',
        'splfileobject::fseek',
        'splfileobject::fwrite',
        'splfileobject::getfileinfo',
        'splfileobject::getpathinfo',
        'splfileobject::openfile',
        'splfileobject::seek',
        'splfileobject::setcsvcontrol',
        'splfileobject::setfileclass',
        'splfileobject::setinfoclass',
        'splfileobject::setmaxlinelen',
        'splfixedarray::fromarray',
        'splfixedarray::offsetset',
        'splmaxheap::compare',
        'splminheap::compare',
        'splobjectstorage::addall',
        'splobjectstorage::attach',
        'splobjectstorage::count',
        'splobjectstorage::offsetset',
        'splobjectstorage::removeall',
        'splobjectstorage::removeallexcept',
        'splobjectstorage::setinfo',
        'splobjectstorage::unserialize',
        'splpriorityqueue::compare',
        'splqueue::offsetset',
        'splqueue::unserialize',
        'splstack::add',
        'splstack::offsetset',
        'splstack::unserialize',
        'spltempfileobject::__construct',
        'spltempfileobject::fgetcsv',
        'spltempfileobject::flock',
        'spltempfileobject::fputcsv',
        'spltempfileobject::fseek',
        'spltempfileobject::fwrite',
        'spltempfileobject::getfileinfo',
        'spltempfileobject::getpathinfo',
        'spltempfileobject::openfile',
        'spltempfileobject::seek',
        'spltempfileobject::setcsvcontrol',
        'spltempfileobject::setfileclass',
        'spltempfileobject::setinfoclass',
        'spltempfileobject::setmaxlinelen',
        'spoofchecker::areconfusable',
        'spoofchecker::issuspicious',
        'spoofchecker::setallowedlocales',
        'spoofchecker::setchecks',
        'spoofchecker::setrestrictionlevel',
        'sqlite3::__construct',
        'sqlite3::open',
        'sqlsrv_connect',
        'sqlsrv_errors',
        'sqlsrv_fetch_array',
        'sqlsrv_fetch_object',
        'sqlsrv_get_field',
        'sqlsrv_prepare',
        'sqlsrv_query',
        'sqlsrv_server_info',
        'ssh2_forward_accept',
        'substr_replace',
        'tidy_getopt',
        'transliterator::transliterate',
        'uconverter::convert',
        'uconverter::fromucallback',
        'uconverter::reasontext',
        'uconverter::transcode',
        'uopz_allow_exit',
        'uopz_get_mock',
        'uopz_get_property',
        'uopz_get_return',
        'uopz_get_static',
        'uopz_set_mock',
        'uopz_set_property',
        'uopz_set_static',
        'uopz_unset_mock',
        'xdiff_file_bdiff',
        'xdiff_file_bdiff_size',
        'xdiff_file_diff',
        'xdiff_file_diff_binary',
        'xdiff_file_merge3',
        'xdiff_file_rabdiff',
        'xdiff_string_bdiff',
        'xdiff_string_bdiff_size',
        'xdiff_string_bpatch',
        'xdiff_string_diff',
        'xdiff_string_diff_binary',
        'xdiff_string_merge3',
        'xdiff_string_patch',
        'xdiff_string_patch_binary',
        'xdiff_string_rabdiff',
        'xmlreader::getattributens',
        'xmlreader::movetoattributens',
        'xmlreader::next',
        'xmlreader::open',
        'xmlreader::xml',
        'xmlrpc_encode_request',
        'xmlrpc_server_add_introspection_data',
        'xmlrpc_server_call_method',
        'xmlrpc_server_destroy',
        'xmlrpc_server_register_introspection_callback',
        'xmlrpc_server_register_method',
        'xsltprocessor::registerphpfunctions',
        'xsltprocessor::transformtodoc',
        'yaml_emit',
        'yaml_emit_file',
        'zip_entry_close',
        'ziparchive::iscompressionmethodsupported',
        'ziparchive::isencryptionmethodsupported',
        'ziparchive::setcompressionindex',
        'ziparchive::setcompressionname',
        'ziparchive::setencryptionindex',
        'zlib_encode',
    ];

    /**
     * List of function names to ignore only for return type checks.
     *
     * @var array<int|string, string|list<string>>
     */
    private static array $ignoredReturnTypeOnlyFunctions = [
        'appenditerator::getinneriterator' => ['8.1', '8.2'],
        'appenditerator::getiteratorindex' => ['8.1', '8.2'],
        'arrayobject::getiterator' => ['8.1', '8.2'],
        'cachingiterator::getinneriterator' => ['8.1', '8.2'],
        'callbackfilteriterator::getinneriterator' => ['8.1', '8.2'],
        'curl_multi_getcontent',
        'datetime::add' => ['8.1', '8.2'],
        'datetime::createfromimmutable' => ['8.1', '8.2'],
        'datetime::createfrominterface',
        'datetime::setdate' => ['8.1', '8.2'],
        'datetime::settimezone' => ['8.1', '8.2'],
        'datetime::sub' => ['8.1', '8.2'],
        'datetimeimmutable::createfrominterface',
        'fiber::getcurrent',
        'filteriterator::getinneriterator' => ['8.1', '8.2'],
        'infiniteiterator::getinneriterator' => ['8.1', '8.2'],
        'iteratoriterator::getinneriterator' => ['8.1', '8.2'],
        'limititerator::getinneriterator' => ['8.1', '8.2'],
        'locale::canonicalize' => ['8.1', '8.2'],
        'locale::getallvariants' => ['8.1', '8.2'],
        'locale::getkeywords' => ['8.1', '8.2'],
        'locale::getprimarylanguage' => ['8.1', '8.2'],
        'locale::getregion' => ['8.1', '8.2'],
        'locale::getscript' => ['8.1', '8.2'],
        'locale::parselocale' => ['8.1', '8.2'],
        'messageformatter::create' => ['8.1', '8.2'],
        'multipleiterator::current' => ['8.1', '8.2'],
        'mysqli::get_charset' => ['8.1', '8.2'],
        'mysqli_stmt::get_warnings' => ['8.1', '8.2'],
        'mysqli_stmt_get_warnings',
        'mysqli_stmt_insert_id',
        'norewinditerator::getinneriterator' => ['8.1', '8.2'],
        'passthru',
        'recursivecachingiterator::getinneriterator' => ['8.1', '8.2'],
        'recursivecallbackfilteriterator::getinneriterator' => ['8.1', '8.2'],
        'recursivefilteriterator::getinneriterator' => ['8.1', '8.2'],
        'recursiveregexiterator::getinneriterator' => ['8.1', '8.2'],
        'reflectionclass::getstaticproperties' => ['8.1', '8.2'],
        'reflectionclass::newinstanceargs' => ['8.1', '8.2'],
        'reflectionfunction::getclosurescopeclass' => ['8.1', '8.2'],
        'reflectionfunction::getclosurethis' => ['8.1', '8.2'],
        'reflectionmethod::getclosurescopeclass' => ['8.1', '8.2'],
        'reflectionmethod::getclosurethis' => ['8.1', '8.2'],
        'reflectionobject::getstaticproperties' => ['8.1', '8.2'],
        'reflectionobject::newinstanceargs' => ['8.1', '8.2'],
        'regexiterator::getinneriterator' => ['8.1', '8.2'],
        'register_shutdown_function' => ['8.0', '8.1'],
        'splfileobject::fscanf' => ['8.1', '8.2'],
        'spltempfileobject::fscanf' => ['8.1', '8.2'],
        'xmlrpc_encode',
        'xmlrpc_server_create',
        'xsltprocessor::transformtoxml' => ['8.1', '8.2'],
    ];

    /**
     * List of function names to ignore because they cannot be reflected.
     *
     * These could be truly inaccessible, or they could be functions removed in newer PHP versions.
     * Removed functions should be removed from CallMap and added to the appropriate delta.
     *
     * @var array<int|string, string|list<string>>
     */
    private static array $ignoredUnreflectableFunctions = [
        'closure::__invoke',
        'curlfile::__wakeup',
        'domimplementation::__construct',
        'generator::__wakeup',
        'gmp::__construct',
        'gmp::__tostring',
        'intliterator::__construct',
        'mysqli::disable_reads_from_master',
        'mysqli::rpl_query_type',
        'mysqli::send_query',
        'mysqli::set_local_infile_default',
        'mysqli::set_local_infile_handler',
        'mysqli_driver::embedded_server_end',
        'mysqli_driver::embedded_server_start',
        'pdo::__sleep',
        'pdo::__wakeup',
        'pdo::cubrid_schema',
        'pdo::pgsqlcopyfromarray',
        'pdo::pgsqlcopyfromfile',
        'pdo::pgsqlcopytoarray',
        'pdo::pgsqlcopytofile',
        'pdo::pgsqlgetnotify',
        'pdo::pgsqlgetpid',
        'pdo::pgsqllobcreate',
        'pdo::pgsqllobopen',
        'pdo::pgsqllobunlink',
        'pdo::sqlitecreateaggregate',
        'pdo::sqlitecreatecollation',
        'pdo::sqlitecreatefunction',
        'pdostatement::__sleep',
        'pdostatement::__wakeup',
        'phar::compressallfilesbzip2',
        'phar::compressallfilesgz',
        'phar::uncompressallfiles',
        'pharfileinfo::iscompressedbzip2',
        'pharfileinfo::iscompressedgz',
        'pharfileinfo::setcompressedbzip2',
        'pharfileinfo::setcompressedgz',
        'pharfileinfo::setuncompressed',
        'simplexmlelement::__get',
        'simplexmlelement::offsetexists',
        'simplexmlelement::offsetget',
        'simplexmlelement::offsetset',
        'simplexmlelement::offsetunset',
        'spldoublylinkedlist::__construct',
        'splfileinfo::__wakeup',
        'splfixedarray::current',
        'splfixedarray::key',
        'splfixedarray::next',
        'splfixedarray::rewind',
        'splfixedarray::valid',
        'splheap::__construct',
        'splmaxheap::__construct',
        'splobjectstorage::__construct',
        'splpriorityqueue::__construct',
        'splstack::__construct',
        'weakmap::__construct',
        'weakmap::current',
        'weakmap::key',
        'weakmap::next',
        'weakmap::rewind',
        'weakmap::valid',
    ];

    private static Codebase $codebase;

    public static function setUpBeforeClass(): void
    {
        $project_analyzer = new ProjectAnalyzer(
            new TestConfig(),
            new Providers(
                new FakeFileProvider(),
                new FakeParserCacheProvider(),
            ),
        );
        self::$codebase = $project_analyzer->getCodebase();
    }

    public function testIgnoresAreSortedAndUnique(): void
    {
        $previousFunction = "";
        foreach (self::$ignoredFunctions as $key => $value) {
            /** @var string */
            $function = is_int($key) ? $value : $key;

            $diff = strcmp($function, $previousFunction);
            $this->assertGreaterThan(0, $diff, "'{$function}' should come before '{$previousFunction}' in InternalCallMapHandlerTest::\$ignoredFunctions");

            $previousFunction = $function;
        }
    }

    /**
     * @covers \Psalm\Internal\Codebase\InternalCallMapHandler::getCallMap
     */
    public function testGetcallmapReturnsAValidCallmap(): void
    {
        $callMap = InternalCallMapHandler::getCallMap();
        self::assertArrayKeysAreStrings($callMap, "Returned CallMap has non-string keys");
        self::assertArrayValuesAreArrays($callMap, "Returned CallMap has non-array values");
        foreach ($callMap as $function => $signature) {
            self::assertArrayKeysAreZeroOrString($signature, "Function " . $function . " in returned CallMap has invalid keys");
            self::assertArrayValuesAreStrings($signature, "Function " . $function . " in returned CallMap has non-string values");
            foreach ($signature as $type) {
                self::assertStringIsParsableType($type, "Function " . $function . " in returned CallMap contains invalid type declaration " . $type);
            }
        }
    }

    /**
     * @return iterable<string, array{0: callable-string, 1: array<int|string, string>}>
     */
    public function callMapEntryProvider(): iterable
    {
        /**
         * This call is needed since InternalCallMapHandler uses the singleton that is initialized by it.
         **/
        new ProjectAnalyzer(
            new TestConfig(),
            new Providers(
                new FakeFileProvider(),
                new FakeParserCacheProvider(),
            ),
        );
        $callMap = InternalCallMapHandler::getCallMap();
        foreach ($callMap as $function => $entry) {
            $classNameEnd = strpos($function, '::');
            if ($classNameEnd !== false) {
                $className = substr($function, 0, $classNameEnd);
                if (!class_exists($className, false)) {
                    continue;
                }
            } elseif (!function_exists($function)) {
                continue;
            }

            // Skip functions with alternate signatures
            if (isset($callMap["$function'1"]) || preg_match("/\'\d$/", $function)) {
                continue;
            }
            // if ($function != 'fprintf') continue;
            yield "$function: " . json_encode($entry) => [$function, $entry];
        }
    }

    private function isIgnored(string $functionName): bool
    {
        if (in_array($functionName, self::$ignoredFunctions)) {
            return true;
        }

        if (isset(self::$ignoredFunctions[$functionName])
            && is_array(self::$ignoredFunctions[$functionName])
            && in_array(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, self::$ignoredFunctions[$functionName])) {
            return true;
        }

        return false;
    }

    private function isReturnTypeOnlyIgnored(string $functionName): bool
    {
        if (in_array($functionName, static::$ignoredReturnTypeOnlyFunctions, true)) {
            return true;
        }

        if (isset(self::$ignoredReturnTypeOnlyFunctions[$functionName])
            && is_array(self::$ignoredReturnTypeOnlyFunctions[$functionName])
            && in_array(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, self::$ignoredReturnTypeOnlyFunctions[$functionName])) {
            return true;
        }

        return false;
    }

    private function isUnreflectableIgnored(string $functionName): bool
    {
        if (in_array($functionName, static::$ignoredUnreflectableFunctions, true)) {
            return true;
        }

        if (isset(self::$ignoredUnreflectableFunctions[$functionName])
            && is_array(self::$ignoredUnreflectableFunctions[$functionName])
            && in_array(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, self::$ignoredUnreflectableFunctions[$functionName])) {
            return true;
        }

        return false;
    }

    /**
     * @depends testIgnoresAreSortedAndUnique
     * @depends testGetcallmapReturnsAValidCallmap
     * @dataProvider callMapEntryProvider
     * @coversNothing
     * @psalm-param callable-string $functionName
     * @param array<int|string, string> $callMapEntry
     */
    public function testIgnoredFunctionsStillFail(string $functionName, array $callMapEntry): void
    {
        $functionIgnored = $this->isIgnored($functionName);
        $unreflectableIgnored = $this->isUnreflectableIgnored($functionName);
        if (!$functionIgnored && !$this->isReturnTypeOnlyIgnored($functionName) && !$unreflectableIgnored) {
            // Dummy assertion to mark it as passed
            $this->assertTrue(true);
            return;
        }

        $function = $this->getReflectionFunction($functionName);
        if ($unreflectableIgnored && $function !== null) {
            $this->fail("Remove '{$functionName}' from InternalCallMapHandlerTest::\$ignoredUnreflectableFunctions");
        } elseif ($function === null) {
            $this->assertTrue(true);
            return;
        }

        /** @var string $entryReturnType */
        $entryReturnType = array_shift($callMapEntry);

        if ($functionIgnored) {
            try {
                /** @var array<string, string> $callMapEntry */
                $this->assertEntryParameters($function, $callMapEntry);
                $this->assertEntryReturnType($function, $entryReturnType);
            } catch (AssertionFailedError $e) {
                $this->assertTrue(true);
                return;
            } catch (ExpectationFailedException $e) {
                $this->assertTrue(true);
                return;
            }
            $this->fail("Remove '{$functionName}' from InternalCallMapHandlerTest::\$ignoredFunctions");
        }

        try {
            $this->assertEntryReturnType($function, $entryReturnType);
        } catch (AssertionFailedError $e) {
            $this->assertTrue(true);
            return;
        } catch (ExpectationFailedException $e) {
            $this->assertTrue(true);
            return;
        }
        $this->fail("Remove '{$functionName}' from InternalCallMapHandlerTest::\$ignoredReturnTypeOnlyFunctions");
    }

    /**
     * This function will test functions that are in the callmap AND currently defined
     *
     * @coversNothing
     * @depends testGetcallmapReturnsAValidCallmap
     * @depends testIgnoresAreSortedAndUnique
     * @dataProvider callMapEntryProvider
     * @psalm-param callable-string $functionName
     * @param array<int|string, string> $callMapEntry
     */
    public function testCallMapCompliesWithReflection(string $functionName, array $callMapEntry): void
    {
        if ($this->isIgnored($functionName)) {
            $this->markTestSkipped("Function $functionName is ignored in config");
        }

        $function = $this->getReflectionFunction($functionName);
        if ($function === null) {
            if (!$this->isUnreflectableIgnored($functionName)) {
                $this->fail('Unable to reflect method. Add name to $ignoredUnreflectableFunctions if exists in latest PHP version.');
            }
            return;
        }

        /** @var string $entryReturnType */
        $entryReturnType = array_shift($callMapEntry);

        /** @var array<string, string> $callMapEntry */
        $this->assertEntryParameters($function, $callMapEntry);

        if (!$this->isReturnTypeOnlyIgnored($functionName)) {
            $this->assertEntryReturnType($function, $entryReturnType);
        }
    }

    /**
     * Returns the correct reflection type for function or method name.
     */
    private function getReflectionFunction(string $functionName): ?ReflectionFunctionAbstract
    {
        try {
            if (strpos($functionName, '::') !== false) {
                return new ReflectionMethod($functionName);
            }

            /** @var callable-string $functionName */
            return new ReflectionFunction($functionName);
        } catch (ReflectionException $e) {
            return null;
        }
    }

    /**
     * @param array<string, string> $entryParameters
     */
    private function assertEntryParameters(ReflectionFunctionAbstract $function, array $entryParameters): void
    {
        /**
         * Parse the parameter names from the map.
         *
         * @var array<string, array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string}>
         */
        $normalizedEntries = [];

        foreach ($entryParameters as $key => $entry) {
            $normalizedKey = $key;
            /**
             * @var array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string} $normalizedEntry
             */
            $normalizedEntry = [
                'variadic' => false,
                'byRef' => false,
                'optional' => false,
                'type' => $entry,
            ];
            if (strncmp($normalizedKey, '&', 1) === 0) {
                $normalizedEntry['byRef'] = true;
                $normalizedKey = substr($normalizedKey, 1);
            }

            if (strncmp($normalizedKey, '...', 3) === 0) {
                $normalizedEntry['variadic'] = true;
                $normalizedKey = substr($normalizedKey, 3);
            }

            // Read the reference mode
            if ($normalizedEntry['byRef']) {
                $parts = explode('_', $normalizedKey, 2);
                if (count($parts) === 2) {
                    if (!($parts[0] === 'rw' || $parts[0] === 'w' || $parts[0] === 'r')) {
                        throw new InvalidArgumentException('Invalid refMode: '.$parts[0]);
                    }
                    $normalizedEntry['refMode'] = $parts[0];
                    $normalizedKey = $parts[1];
                } else {
                    $normalizedEntry['refMode'] = 'rw';
                }
            }

            // Strip prefixes.
            if (substr($normalizedKey, -1, 1) === "=") {
                $normalizedEntry['optional'] = true;
                $normalizedKey = substr($normalizedKey, 0, -1);
            }

            //$this->assertTrue($this->hasParameter($function, $normalizedKey), "Calmap has extra param entry {$normalizedKey}");

            $normalizedEntry['name'] = $normalizedKey;
            $normalizedEntries[$normalizedKey] = $normalizedEntry;
        }

        foreach ($function->getParameters() as $parameter) {
            $this->assertArrayHasKey($parameter->getName(), $normalizedEntries, "Callmap is missing entry for param {$parameter->getName()} in {$function->getName()}: " . print_r($normalizedEntries, true));
            $this->assertParameter($normalizedEntries[$parameter->getName()], $parameter);
        }
    }

    /* Used by above assert
    private function hasParameter(ReflectionFunctionAbstract $function, string $name): bool
    {
        foreach ($function->getParameters() as $parameter)
        {
            if ($parameter->getName() === $name) {
                return true;
            }
        }

        return false;
    }
    */

    /**
     * @param array{byRef: bool, name?: string, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string} $normalizedEntry
     */
    private function assertParameter(array $normalizedEntry, ReflectionParameter $param): void
    {
        $name = $param->getName();
        $this->assertSame($param->isOptional(), $normalizedEntry['optional'], "Expected param '{$name}' to " . ($param->isOptional() ? "be" : "not be") . " optional");
        $this->assertSame($param->isVariadic(), $normalizedEntry['variadic'], "Expected param '{$name}' to " . ($param->isVariadic() ? "be" : "not be") . " variadic");
        $this->assertSame($param->isPassedByReference(), $normalizedEntry['byRef'], "Expected param '{$name}' to " . ($param->isPassedByReference() ? "be" : "not be") . " by reference");

        $expectedType = $param->getType();

        if (isset($expectedType) && !empty($normalizedEntry['type'])) {
            $this->assertTypeValidity($expectedType, $normalizedEntry['type'], "Param '{$name}'");
        }
    }

    public function assertEntryReturnType(ReflectionFunctionAbstract $function, string $entryReturnType): void
    {
        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            /** @var ReflectionType|null $expectedType */
            $expectedType = $function->hasTentativeReturnType() ? $function->getTentativeReturnType() : $function->getReturnType();
        } else {
            $expectedType = $function->getReturnType();
        }

        $this->assertNotEmpty($entryReturnType, 'CallMap entry has empty return type');
        if ($expectedType !== null) {
            $this->assertTypeValidity($expectedType, $entryReturnType, 'Return');
        }
    }

    /**
     * Since string equality is too strict, we do some extra checking here
     */
    private function assertTypeValidity(ReflectionType $reflected, string $specified, string $msgPrefix): void
    {
        $expectedType = Reflection::getPsalmTypeFromReflectionType($reflected);
        $callMapType = Type::parseString($specified);

        try {
            $this->assertTrue(UnionTypeComparator::isContainedBy(self::$codebase, $callMapType, $expectedType), "{$msgPrefix} type '{$specified}' should be contained by reflected type '{$reflected}'");
        } catch (InvalidArgumentException $e) {
            if (preg_match('/^Could not get class storage for (.*)$/', $e->getMessage(), $matches)
                && !class_exists($matches[1])
            ) {
                $this->fail("Class used in CallMap does not exist: {$matches[1]}");
            }
        }

        // Reflection::getPsalmTypeFromReflectionType adds |null to mixed types so skip comparison
        if (!$expectedType->hasMixed()) {
            $this->assertSame($expectedType->isNullable(), $callMapType->isNullable(), "{$msgPrefix} type '{$specified}' should be nullable");
        }
    }
}
