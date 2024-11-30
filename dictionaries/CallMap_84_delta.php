<?php // phpcs:ignoreFile

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
        'handler=' => 'null|string',
        'permission=' => 'int',
        'map_size=' => 'int',
        'flags=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'Dba\\Connection|false',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => 'null|string',
        'permission=' => 'int',
        'map_size=' => 'int',
        'flags=' => 'int|null',
      ),
    ),
    'dba_popen' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => 'null|string',
        'permission=' => 'int',
        'map_size=' => 'int',
        'flags=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'Dba\\Connection|false',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => 'null|string',
        'permission=' => 'int',
        'map_size=' => 'int',
        'flags=' => 'int|null',
      ),
    ),
    'DOMDocument::registerNodeClass' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'baseClass' => 'string',
        'extendedClass' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'true',
        'baseClass' => 'string',
        'extendedClass' => 'null|string',
      ),
    ),
    'DOMImplementation::createDocument' => 
    array (
      'old' => 
      array (
        0 => 'DOMDocument|false',
        'namespace=' => 'null|string',
        'qualifiedName=' => 'string',
        'doctype=' => 'DOMDocumentType|null',
      ),
      'new' => 
      array (
        0 => 'DOMDocument',
        'namespace=' => 'null|string',
        'qualifiedName=' => 'string',
        'doctype=' => 'DOMDocumentType|null',
      ),
    ),
    'exit' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'status' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'never',
        'status' => 'int|string',
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
        0 => 'bool|string',
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
        'field=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'true',
        'field=' => 'int|null',
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
        'field=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'true',
        'field=' => 'int|null',
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
        0 => 'true',
        'statement' => 'resource',
        'mode' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'statement' => 'Odbc\\Result',
        'mode' => 'int',
      ),
    ),
    'odbc_columnprivileges' => 
    array (
      'old' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog' => 'null|string',
        'schema' => 'string',
        'table' => 'string',
        'column' => 'string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'Odbc\\Connection',
        'catalog' => 'null|string',
        'schema' => 'string',
        'table' => 'string',
        'column' => 'string',
      ),
    ),
    'odbc_columns' => 
    array (
      'old' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog=' => 'null|string',
        'schema=' => 'null|string',
        'table=' => 'null|string',
        'column=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'Odbc\\Connection',
        'catalog=' => 'null|string',
        'schema=' => 'null|string',
        'table=' => 'null|string',
        'column=' => 'null|string',
      ),
    ),
    'odbc_connect' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
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
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'query' => 'string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'Odbc\\Connection',
        'query' => 'string',
      ),
    ),
    'odbc_exec' => 
    array (
      'old' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'query' => 'string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'Odbc\\Connection',
        'query' => 'string',
      ),
    ),
    'odbc_foreignkeys' => 
    array (
      'old' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'pk_catalog' => 'null|string',
        'pk_schema' => 'string',
        'pk_table' => 'string',
        'fk_catalog' => 'string',
        'fk_schema' => 'string',
        'fk_table' => 'string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'Odbc\\Connection',
        'pk_catalog' => 'null|string',
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
        0 => 'true',
        'statement' => 'resource',
      ),
      'new' => 
      array (
        0 => 'true',
        'statement' => 'Odbc\\Result',
      ),
    ),
    'odbc_gettypeinfo' => 
    array (
      'old' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'data_type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'Odbc\\Connection',
        'data_type=' => 'int',
      ),
    ),
    'odbc_longreadlen' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'statement' => 'resource',
        'length' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'statement' => 'Odbc\\Result',
        'length' => 'int',
      ),
    ),
    'odbc_pconnect' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
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
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'query' => 'string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'Odbc\\Connection',
        'query' => 'string',
      ),
    ),
    'odbc_primarykeys' => 
    array (
      'old' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog' => 'null|string',
        'schema' => 'string',
        'table' => 'string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'Odbc\\Connection',
        'catalog' => 'null|string',
        'schema' => 'string',
        'table' => 'string',
      ),
    ),
    'odbc_procedurecolumns' => 
    array (
      'old' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog=' => 'null|string',
        'schema=' => 'null|string',
        'procedure=' => 'null|string',
        'column=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'Odbc\\Connection',
        'catalog=' => 'null|string',
        'schema=' => 'null|string',
        'procedure=' => 'null|string',
        'column=' => 'null|string',
      ),
    ),
    'odbc_procedures' => 
    array (
      'old' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog=' => 'null|string',
        'schema=' => 'null|string',
        'procedure=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'Odbc\\Connection',
        'catalog=' => 'null|string',
        'schema=' => 'null|string',
        'procedure=' => 'null|string',
      ),
    ),
    'odbc_specialcolumns' => 
    array (
      'old' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'type' => 'int',
        'catalog' => 'null|string',
        'schema' => 'string',
        'table' => 'string',
        'scope' => 'int',
        'nullable' => 'int',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'Odbc\\Connection',
        'type' => 'int',
        'catalog' => 'null|string',
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
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog' => 'null|string',
        'schema' => 'string',
        'table' => 'string',
        'unique' => 'int',
        'accuracy' => 'int',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'Odbc\\Connection',
        'catalog' => 'null|string',
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
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog' => 'null|string',
        'schema' => 'string',
        'table' => 'string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'Odbc\\Connection',
        'catalog' => 'null|string',
        'schema' => 'string',
        'table' => 'string',
      ),
    ),
    'odbc_tables' => 
    array (
      'old' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog=' => 'null|string',
        'schema=' => 'null|string',
        'table=' => 'null|string',
        'types=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Result|false',
        'odbc' => 'Odbc\\Connection',
        'catalog=' => 'null|string',
        'schema=' => 'null|string',
        'table=' => 'null|string',
        'types=' => 'null|string',
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
        'connection=' => 'PgSql\\Connection|null',
      ),
      'new' => 
      array (
        0 => 'true',
        'connection=' => 'PgSql\\Connection|null',
      ),
    ),
    'pg_untrace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection=' => 'PgSql\\Connection|null',
      ),
      'new' => 
      array (
        0 => 'true',
        'connection=' => 'PgSql\\Connection|null',
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
        'index=' => 'null|string',
        'webIndex=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'true',
        'index=' => 'null|string',
        'webIndex=' => 'null|string',
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
        'index' => 'int|string',
        'fallback=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'ResourceBundle|array<array-key, mixed>|int|null|string',
        'index' => 'int|string',
        'fallback=' => 'bool',
      ),
    ),
    'resourcebundle_get' => 
    array (
      'old' => 
      array (
        0 => 'mixed|null',
        'bundle' => 'ResourceBundle',
        'index' => 'int|string',
        'fallback=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'ResourceBundle|array<array-key, mixed>|int|null|string',
        'bundle' => 'ResourceBundle',
        'index' => 'int|string',
        'fallback=' => 'bool',
      ),
    ),
    'SoapClient::__setCookie' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
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
        0 => 'null|object',
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
        'context' => 'mixed',
        'wrapper_or_options' => 'string',
        'option_name' => 'string',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        'context' => 'mixed',
        'wrapper_or_options' => 'string',
        'option_name' => 'string',
        'value' => 'mixed',
      ),
    ),
    'stream_context_set_params' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'context' => 'resource',
        'params' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'true',
        'context' => 'resource',
        'params' => 'array<array-key, mixed>',
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
        'filename' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'true',
        'filename' => 'null|string',
      ),
    ),
    'dba_close' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'dba' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
        'dba' => 'Dba\\Connection',
      ),
    ),
    'dba_delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'array<array-key, mixed>|string',
        'dba' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'array<array-key, mixed>|string',
        'dba' => 'Dba\\Connection',
      ),
    ),
    'dba_exists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'array<array-key, mixed>|string',
        'dba' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'array<array-key, mixed>|string',
        'dba' => 'Dba\\Connection',
      ),
    ),
    'dba_firstkey' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'dba' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'dba' => 'Dba\\Connection',
      ),
    ),
    'dba_insert' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'array<array-key, mixed>|string',
        'value' => 'string',
        'dba' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'array<array-key, mixed>|string',
        'value' => 'string',
        'dba' => 'Dba\\Connection',
      ),
    ),
    'dba_nextkey' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'dba' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'dba' => 'Dba\\Connection',
      ),
    ),
    'dba_optimize' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dba' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dba' => 'Dba\\Connection',
      ),
    ),
    'dba_replace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'array<array-key, mixed>|string',
        'value' => 'string',
        'dba' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'array<array-key, mixed>|string',
        'value' => 'string',
        'dba' => 'Dba\\Connection',
      ),
    ),
    'dba_sync' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dba' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dba' => 'Dba\\Connection',
      ),
    ),
    'imagegd' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'file=' => 'null|resource|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'file=' => 'null|string',
      ),
    ),
    'imagegd2' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'file=' => 'null|resource|string',
        'chunk_size=' => 'int',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'file=' => 'null|string',
        'chunk_size=' => 'int',
        'mode=' => 'int',
      ),
    ),
    'odbc_autocommit' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'odbc' => 'resource',
        'enable=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'odbc' => 'Odbc\\Connection',
        'enable=' => 'bool',
      ),
    ),
    'odbc_close' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'odbc' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
        'odbc' => 'Odbc\\Connection',
      ),
    ),
    'odbc_commit' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'odbc' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'odbc' => 'Odbc\\Connection',
      ),
    ),
    'odbc_cursor' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'statement' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'statement' => 'Odbc\\Result',
      ),
    ),
    'odbc_data_source' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'odbc' => 'resource',
        'fetch_type' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'odbc' => 'Odbc\\Connection',
        'fetch_type' => 'int',
      ),
    ),
    'odbc_error' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'odbc=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'odbc=' => 'Odbc\\Connection|null',
      ),
    ),
    'odbc_errormsg' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'odbc=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'odbc=' => 'Odbc\\Connection|null',
      ),
    ),
    'odbc_execute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'statement' => 'resource',
        'params=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'statement' => 'Odbc\\Result',
        'params=' => 'array<array-key, mixed>',
      ),
    ),
    'odbc_fetch_row' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'statement' => 'resource',
        'row=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'statement' => 'Odbc\\Result',
        'row=' => 'int|null',
      ),
    ),
    'odbc_field_len' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'statement' => 'resource',
        'field' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'statement' => 'Odbc\\Result',
        'field' => 'int',
      ),
    ),
    'odbc_field_name' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'statement' => 'resource',
        'field' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'statement' => 'Odbc\\Result',
        'field' => 'int',
      ),
    ),
    'odbc_field_num' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'statement' => 'resource',
        'field' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'statement' => 'Odbc\\Result',
        'field' => 'string',
      ),
    ),
    'odbc_field_precision' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'statement' => 'resource',
        'field' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'statement' => 'Odbc\\Result',
        'field' => 'int',
      ),
    ),
    'odbc_field_scale' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'statement' => 'resource',
        'field' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'statement' => 'Odbc\\Result',
        'field' => 'int',
      ),
    ),
    'odbc_field_type' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'statement' => 'resource',
        'field' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'statement' => 'Odbc\\Result',
        'field' => 'int',
      ),
    ),
    'odbc_next_result' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'statement' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'statement' => 'Odbc\\Result',
      ),
    ),
    'odbc_num_fields' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'statement' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'statement' => 'Odbc\\Result',
      ),
    ),
    'odbc_num_rows' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'statement' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'statement' => 'Odbc\\Result',
      ),
    ),
    'odbc_result' => 
    array (
      'old' => 
      array (
        0 => 'bool|null|string',
        'statement' => 'resource',
        'field' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool|null|string',
        'statement' => 'Odbc\\Result',
        'field' => 'int|string',
      ),
    ),
    'odbc_result_all' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'statement' => 'resource',
        'format=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'statement' => 'Odbc\\Result',
        'format=' => 'string',
      ),
    ),
    'odbc_rollback' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'odbc' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'odbc' => 'Odbc\\Connection',
      ),
    ),
    'odbc_setoption' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'odbc' => 'resource',
        'which' => 'int',
        'option' => 'int',
        'value' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'odbc' => 'Odbc\\Connection|Odbc\\Result',
        'which' => 'int',
        'option' => 'int',
        'value' => 'int',
      ),
    ),
    'preg_split' => 
    array (
      'old' => 
      array (
        0 => 'false|list<string>',
        'pattern' => 'string',
        'subject' => 'string',
        'limit' => 'int',
        'flags=' => 'null',
      ),
      'new' => 
      array (
        0 => 'false|list<string>',
        'pattern' => 'string',
        'subject' => 'string',
        'limit' => 'int',
        'flags=' => 'int',
      ),
    ),
    'SoapClient::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'wsdl' => 'mixed',
        'options=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'void',
        'wsdl' => 'null|string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'stream_bucket_append' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'brigade' => 'resource',
        'bucket' => 'object',
      ),
      'new' => 
      array (
        0 => 'void',
        'brigade' => 'resource',
        'bucket' => 'StreamBucket',
      ),
    ),
    'stream_bucket_prepend' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'brigade' => 'resource',
        'bucket' => 'object',
      ),
      'new' => 
      array (
        0 => 'void',
        'brigade' => 'resource',
        'bucket' => 'StreamBucket',
      ),
    ),
    'xml_set_element_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'start_handler' => 'callable',
        'end_handler' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'start_handler' => 'callable|null',
        'end_handler' => 'callable',
      ),
    ),
    'xml_set_start_namespace_decl_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable|null',
      ),
    ),
  ),
);