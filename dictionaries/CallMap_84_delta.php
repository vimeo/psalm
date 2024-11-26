<?php // phpcs:ignoreFile
namespace Phan\Language\Internal;

return array (
  'added' => 
  array (
  ),
  'removed' => 
  array (
  ),
  'changed' => 
  array (
    'Collator::setStrength' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'strength' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'strength' => 'int',
      ),
    ),
    'collator_set_strength' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'object' => 'collator',
        'strength' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'object' => 'collator',
        'strength' => 'int',
      ),
    ),
    'dba_open' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => '?string',
        'permission=' => 'int',
        'map_size=' => 'int',
        'flags=' => '?int',
      ),
      'new' => 
      array (
        0 => 'Dba\\Connection|false',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => '?string',
        'permission=' => 'int',
        'map_size=' => 'int',
        'flags=' => '?int',
      ),
    ),
    'dba_popen' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => '?string',
        'permission=' => 'int',
        'map_size=' => 'int',
        'flags=' => '?int',
      ),
      'new' => 
      array (
        0 => 'Dba\\Connection|false',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => '?string',
        'permission=' => 'int',
        'map_size=' => 'int',
        'flags=' => '?int',
      ),
    ),
    'DOMDocument::registerNodeClass' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'baseClass' => 'string',
        'extendedClass' => '?string',
      ),
      'new' => 
      array (
        0 => 'true',
        'baseClass' => 'string',
        'extendedClass' => '?string',
      ),
    ),
    'DOMImplementation::createDocument' => 
    array (
      'old' => 
      array (
        0 => 'DOMDocument|false',
        'namespace=' => '?string',
        'qualifiedName=' => 'string',
        'doctype=' => '?DOMDocumentType',
      ),
      'new' => 
      array (
        0 => 'DOMDocument',
        'namespace=' => '?string',
        'qualifiedName=' => 'string',
        'doctype=' => '?DOMDocumentType',
      ),
    ),
    'exit' => 
    array (
      'old' => 
      array (
        0 => '',
        'status' => 'string|int',
      ),
      'new' => 
      array (
        0 => 'never',
        'status' => 'string|int',
      ),
    ),
    'finfo::set_flags' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'flags' => 'int',
      ),
    ),
    'finfo_set_flags' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'finfo' => 'finfo',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'finfo' => 'finfo',
        'flags' => 'int',
      ),
    ),
    'hash_update' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'context' => 'HashContext',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'context' => 'HashContext',
        'data' => 'string',
      ),
    ),
    'highlight_string' => 
    array (
      'old' => 
      array (
        0 => 'string|bool',
        'string' => 'string',
        'return=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string|true',
        'string' => 'string',
        'return=' => 'bool',
      ),
    ),
    'intlcal_set' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'year' => 'int',
        'month' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'calendar' => 'IntlCalendar',
        'year' => 'int',
        'month' => 'int',
      ),
    ),
    'IntlCalendar::clear' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'field=' => '?int',
      ),
      'new' => 
      array (
        0 => 'true',
        'field=' => '?int',
      ),
    ),
    'IntlCalendar::set' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'field' => 'int',
        'value' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'field' => 'int',
        'value' => 'int',
      ),
    ),
    'IntlCalendar::setFirstDayOfWeek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dayOfWeek' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'dayOfWeek' => 'int',
      ),
    ),
    'IntlCalendar::setMinimalDaysInFirstWeek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'days' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'days' => 'int',
      ),
    ),
    'IntlGregorianCalendar::clear' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'field=' => '?int',
      ),
      'new' => 
      array (
        0 => 'true',
        'field=' => '?int',
      ),
    ),
    'IntlGregorianCalendar::set' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'field' => 'int',
        'value' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'field' => 'int',
        'value' => 'int',
      ),
    ),
    'IntlGregorianCalendar::setFirstDayOfWeek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dayOfWeek' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'dayOfWeek' => 'int',
      ),
    ),
    'IntlGregorianCalendar::setMinimalDaysInFirstWeek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'days' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'days' => 'int',
      ),
    ),
    'Locale::setDefault' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'locale' => 'string',
      ),
    ),
    'mysqli_report' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'flags' => 'int',
      ),
    ),
    'odbc_binmode' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'statement' => 'resource',
        'mode' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'statement' => 'resource',
        'mode' => 'int',
      ),
    ),
    'odbc_columnprivileges' => 
    array (
      'old' => 
      array (
        0 => 'resource|false',
        'odbc' => 'resource',
        'catalog' => '?string',
        'schema' => 'string',
        'table' => 'string',
        'column' => 'string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog' => '?string',
        'schema' => 'string',
        'table' => 'string',
        'column' => 'string',
      ),
    ),
    'odbc_columns' => 
    array (
      'old' => 
      array (
        0 => 'resource|false',
        'odbc' => 'resource',
        'catalog=' => '?string',
        'schema=' => '?string',
        'table=' => '?string',
        'column=' => '?string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog=' => '?string',
        'schema=' => '?string',
        'table=' => '?string',
        'column=' => '?string',
      ),
    ),
    'odbc_connect' => 
    array (
      'old' => 
      array (
        0 => 'resource|false',
        'dsn' => 'string',
        'user' => 'string',
        'password' => 'string',
        'cursor_option=' => 'int',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Connection|false',
        'dsn' => 'string',
        'user' => 'string',
        'password' => 'string',
        'cursor_option=' => 'int',
      ),
    ),
    'odbc_do' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'odbc' => 'resource',
        'query' => 'string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'query' => 'string',
      ),
    ),
    'odbc_exec' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'odbc' => 'resource',
        'query' => 'string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'query' => 'string',
      ),
    ),
    'odbc_foreignkeys' => 
    array (
      'old' => 
      array (
        0 => 'resource|false',
        'odbc' => 'resource',
        'pk_catalog' => '?string',
        'pk_schema' => 'string',
        'pk_table' => 'string',
        'fk_catalog' => 'string',
        'fk_schema' => 'string',
        'fk_table' => 'string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'pk_catalog' => '?string',
        'pk_schema' => 'string',
        'pk_table' => 'string',
        'fk_catalog' => 'string',
        'fk_schema' => 'string',
        'fk_table' => 'string',
      ),
    ),
    'odbc_free_result' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'statement' => 'resource',
      ),
      'new' => 
      array (
        0 => 'true',
        'statement' => 'resource',
      ),
    ),
    'odbc_gettypeinfo' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'odbc' => 'resource',
        'data_type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'data_type=' => 'int',
      ),
    ),
    'odbc_longreadlen' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'statement' => 'resource',
        'length' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'statement' => 'resource',
        'length' => 'int',
      ),
    ),
    'odbc_pconnect' => 
    array (
      'old' => 
      array (
        0 => 'resource|false',
        'dsn' => 'string',
        'user' => 'string',
        'password' => 'string',
        'cursor_option=' => 'int',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Connection|false',
        'dsn' => 'string',
        'user' => 'string',
        'password' => 'string',
        'cursor_option=' => 'int',
      ),
    ),
    'odbc_prepare' => 
    array (
      'old' => 
      array (
        0 => 'resource|false',
        'odbc' => 'resource',
        'query' => 'string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'query' => 'string',
      ),
    ),
    'odbc_primarykeys' => 
    array (
      'old' => 
      array (
        0 => 'resource|false',
        'odbc' => 'resource',
        'catalog' => '?string',
        'schema' => 'string',
        'table' => 'string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog' => '?string',
        'schema' => 'string',
        'table' => 'string',
      ),
    ),
    'odbc_procedurecolumns' => 
    array (
      'old' => 
      array (
        0 => 'resource|false',
        'odbc' => 'resource',
        'catalog=' => '?string',
        'schema=' => '?string',
        'procedure=' => '?string',
        'column=' => '?string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog=' => '?string',
        'schema=' => '?string',
        'procedure=' => '?string',
        'column=' => '?string',
      ),
    ),
    'odbc_procedures' => 
    array (
      'old' => 
      array (
        0 => 'resource|false',
        'odbc' => 'resource',
        'catalog=' => '?string',
        'schema=' => '?string',
        'procedure=' => '?string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog=' => '?string',
        'schema=' => '?string',
        'procedure=' => '?string',
      ),
    ),
    'odbc_specialcolumns' => 
    array (
      'old' => 
      array (
        0 => 'resource|false',
        'odbc' => 'resource',
        'type' => 'int',
        'catalog' => '?string',
        'schema' => 'string',
        'table' => 'string',
        'scope' => 'int',
        'nullable' => 'int',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'type' => 'int',
        'catalog' => '?string',
        'schema' => 'string',
        'table' => 'string',
        'scope' => 'int',
        'nullable' => 'int',
      ),
    ),
    'odbc_statistics' => 
    array (
      'old' => 
      array (
        0 => 'resource|false',
        'odbc' => 'resource',
        'catalog' => '?string',
        'schema' => 'string',
        'table' => 'string',
        'unique' => 'int',
        'accuracy' => 'int',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog' => '?string',
        'schema' => 'string',
        'table' => 'string',
        'unique' => 'int',
        'accuracy' => 'int',
      ),
    ),
    'odbc_tableprivileges' => 
    array (
      'old' => 
      array (
        0 => 'resource|false',
        'odbc' => 'resource',
        'catalog' => '?string',
        'schema' => 'string',
        'table' => 'string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog' => '?string',
        'schema' => 'string',
        'table' => 'string',
      ),
    ),
    'odbc_tables' => 
    array (
      'old' => 
      array (
        0 => 'resource|false',
        'odbc' => 'resource',
        'catalog=' => '?string',
        'schema=' => '?string',
        'table=' => '?string',
        'types=' => '?string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog=' => '?string',
        'schema=' => '?string',
        'table=' => '?string',
        'types=' => '?string',
      ),
    ),
    'PDOStatement::setFetchMode' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'mode' => 'int',
        '...args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        'mode' => 'int',
        '...args=' => 'mixed',
      ),
    ),
    'pg_close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection=' => '?\\PgSql\\Connection',
      ),
      'new' => 
      array (
        0 => 'true',
        'connection=' => '?\\PgSql\\Connection',
      ),
    ),
    'pg_untrace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection=' => '?\\PgSql\\Connection',
      ),
      'new' => 
      array (
        0 => 'true',
        'connection=' => '?\\PgSql\\Connection',
      ),
    ),
    'Phar::copy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'from' => 'string',
        'to' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'from' => 'string',
        'to' => 'string',
      ),
    ),
    'Phar::decompressFiles' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'Phar::delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'localName' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'localName' => 'string',
      ),
    ),
    'Phar::delMetadata' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'Phar::setAlias' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'alias' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'alias' => 'string',
      ),
    ),
    'Phar::setDefaultStub' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'index=' => '?string',
        'webIndex=' => '?string',
      ),
      'new' => 
      array (
        0 => 'true',
        'index=' => '?string',
        'webIndex=' => '?string',
      ),
    ),
    'Phar::setStub' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'stub' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'stub' => 'string',
        'length=' => 'int',
      ),
    ),
    'Phar::unlinkArchive' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'filename' => 'string',
      ),
    ),
    'PharData::copy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'from' => 'string',
        'to' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'from' => 'string',
        'to' => 'string',
      ),
    ),
    'PharData::decompressFiles' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'PharData::delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'localName' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'localName' => 'string',
      ),
    ),
    'PharData::delMetadata' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'PharData::setStub' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'stub' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'stub' => 'string',
        'length=' => 'int',
      ),
    ),
    'PharFileInfo::compress' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'compression' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'compression' => 'int',
      ),
    ),
    'PharFileInfo::decompress' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'PharFileInfo::delMetadata' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'ResourceBundle::get' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'index' => 'string|int',
        'fallback=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'ResourceBundle|array<array-key, mixed>|int|null|string',
        'index' => 'string|int',
        'fallback=' => 'bool',
      ),
    ),
    'resourcebundle_get' => 
    array (
      'old' => 
      array (
        0 => 'mixed|null',
        'bundle' => 'ResourceBundle',
        'index' => 'string|int',
        'fallback=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'ResourceBundle|array<array-key, mixed>|int|null|string',
        'bundle' => 'ResourceBundle',
        'index' => 'string|int',
        'fallback=' => 'bool',
      ),
    ),
    'SoapClient::__setCookie' => 
    array (
      'old' => 
      array (
        0 => '',
        'name' => 'string',
        'value=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'name' => 'string',
        'value=' => 'string',
      ),
    ),
    'SplFixedArray::setSize' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'size' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'size' => 'int',
      ),
    ),
    'SplHeap::insert' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        'value' => 'mixed',
      ),
    ),
    'SplPriorityQueue::insert' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
        'priority' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        'value' => 'mixed',
        'priority' => 'mixed',
      ),
    ),
    'SplPriorityQueue::recoverFromCorruption' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'SQLite3Result::finalize' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'SQLite3Stmt::close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'stream_bucket_make_writeable' => 
    array (
      'old' => 
      array (
        0 => '?object',
        'brigade' => 'resource',
      ),
      'new' => 
      array (
        0 => 'StreamBucket|null',
        'brigade' => 'resource',
      ),
    ),
    'stream_bucket_new' => 
    array (
      'old' => 
      array (
        0 => 'object',
        'stream' => 'resource',
        'buffer' => 'string',
      ),
      'new' => 
      array (
        0 => 'StreamBucket',
        'stream' => 'resource',
        'buffer' => 'string',
      ),
    ),
    'stream_context_set_option' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'context' => '',
        'wrapper_or_options' => 'string',
        'option_name' => 'string',
        'value' => '',
      ),
      'new' => 
      array (
        0 => 'true',
        'context' => '',
        'wrapper_or_options' => 'string',
        'option_name' => 'string',
        'value' => '',
      ),
    ),
    'stream_context_set_params' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'context' => 'resource',
        'params' => 'array',
      ),
      'new' => 
      array (
        0 => 'true',
        'context' => 'resource',
        'params' => 'array',
      ),
    ),
    'trigger_error' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'message' => 'string',
        'error_level=' => '256|512|1024|16384',
      ),
      'new' => 
      array (
        0 => 'true',
        'message' => 'string',
        'error_level=' => '256|512|1024|16384',
      ),
    ),
    'user_error' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'message' => 'string',
        'error_level=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'message' => 'string',
        'error_level=' => 'int',
      ),
    ),
    'XMLReader::close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'XSLTProcessor::setProfiling' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => '?string',
      ),
      'new' => 
      array (
        0 => 'true',
        'filename' => '?string',
      ),
    ),
  ),
);