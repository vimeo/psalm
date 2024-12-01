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
    'collator::setstrength' => 
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
    'domdocument::registernodeclass' => 
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
    'domimplementation::createdocument' => 
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
    'intlcalendar::clear' => 
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
    'intlcalendar::set' => 
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
    'intlcalendar::setfirstdayofweek' => 
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
    'intlcalendar::setminimaldaysinfirstweek' => 
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
    'intlgregoriancalendar::clear' => 
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
    'intlgregoriancalendar::set' => 
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
    'intlgregoriancalendar::setfirstdayofweek' => 
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
    'intlgregoriancalendar::setminimaldaysinfirstweek' => 
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
    'locale::setdefault' => 
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
        0 => 'Odbc\\Connection|false',
        'dsn' => 'string',
        'user' => 'string',
        'password' => 'string',
        'cursor_option=' => 'int',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Connection|false',
        'dsn' => 'string',
        'user' => 'null|string',
        'password' => 'null|string',
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
        0 => 'Odbc\\Connection|false',
        'dsn' => 'string',
        'user' => 'string',
        'password' => 'string',
        'cursor_option=' => 'int',
      ),
      'new' => 
      array (
        0 => 'Odbc\\Connection|false',
        'dsn' => 'string',
        'user' => 'null|string',
        'password' => 'null|string',
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
    'pdostatement::setfetchmode' => 
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
    'phar::copy' => 
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
    'phar::decompressfiles' => 
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
    'phar::delete' => 
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
    'phar::delmetadata' => 
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
    'phar::setalias' => 
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
    'phar::setdefaultstub' => 
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
    'phar::setstub' => 
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
    'phar::unlinkarchive' => 
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
    'phardata::copy' => 
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
    'phardata::decompressfiles' => 
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
    'phardata::delete' => 
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
    'phardata::delmetadata' => 
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
    'phardata::setstub' => 
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
    'pharfileinfo::compress' => 
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
    'pharfileinfo::decompress' => 
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
    'pharfileinfo::delmetadata' => 
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
    'resourcebundle::get' => 
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
    'soapclient::__setcookie' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'name' => 'string',
        'value=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'name' => 'string',
        'value=' => 'null|string',
      ),
    ),
    'splfixedarray::setsize' => 
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
    'splheap::insert' => 
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
    'splpriorityqueue::insert' => 
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
    'splpriorityqueue::recoverfromcorruption' => 
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
    'sqlite3result::finalize' => 
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
    'sqlite3stmt::close' => 
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
        0 => 'true',
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
        'option_name' => 'null|string',
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
    'xmlreader::close' => 
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
    'xsltprocessor::setprofiling' => 
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
        'odbc' => 'Odbc\\Connection',
        'enable=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'odbc' => 'Odbc\\Connection',
        'enable=' => 'bool|null',
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
        'odbc' => 'Odbc\\Connection',
        'fetch_type' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|null',
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
    'soapclient::__construct' => 
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
        'start_handler' => 'callable|null',
        'end_handler' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'start_handler' => 'callable|null',
        'end_handler' => 'callable|null',
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
    'appenditerator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'appenditerator::getiteratorindex' => 
    array (
      'old' => 
      array (
        0 => 'int',
      ),
      'new' => 
      array (
        0 => 'int|null',
      ),
    ),
    'cachingiterator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'iterator' => 'Iterator',
        'flags=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'iterator' => 'Iterator',
        'flags=' => 'int',
      ),
    ),
    'cachingiterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'callbackfilteriterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'curl_multi_getcontent' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'handle' => 'CurlHandle',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'handle' => 'CurlHandle',
      ),
    ),
    'filteriterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'fscanf' => 
    array (
      'old' => 
      array (
        0 => 'list<mixed>',
        'stream' => 'resource',
        'format' => 'string',
      ),
      'new' => 
      array (
        0 => 'list<mixed>|null',
        'stream' => 'resource',
        'format' => 'string',
      ),
    ),
    'getenv' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'name' => 'string',
        'local_only=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'name' => 'null|string',
        'local_only=' => 'bool',
      ),
    ),
    'imagefilledpolygon' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'points' => 'array<array-key, mixed>',
        'num_points_or_color' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'points' => 'array<array-key, mixed>',
        'num_points_or_color' => 'int',
        'color' => 'int|null',
      ),
    ),
    'imageopenpolygon' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'points' => 'array<array-key, mixed>',
        'num_points' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'points' => 'array<array-key, mixed>',
        'num_points' => 'int',
        'color' => 'int|null',
      ),
    ),
    'imagepolygon' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'points' => 'array<array-key, mixed>',
        'num_points_or_color' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'points' => 'array<array-key, mixed>',
        'num_points_or_color' => 'int',
        'color' => 'int|null',
      ),
    ),
    'implode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'separator' => 'string',
        'array' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'string',
        'separator' => 'string',
        'array' => 'array<array-key, mixed>|null',
      ),
    ),
    'infiniteiterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'iteratoriterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'join' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'separator' => 'string',
        'array' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'string',
        'separator' => 'string',
        'array' => 'array<array-key, mixed>|null',
      ),
    ),
    'limititerator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'locale::getallvariants' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
        'locale' => 'string',
      ),
    ),
    'locale::getkeywords' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|null',
        'locale' => 'string',
      ),
    ),
    'locale::getprimarylanguage' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'locale' => 'string',
      ),
    ),
    'locale::getregion' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'locale' => 'string',
      ),
    ),
    'locale::getscript' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'locale' => 'string',
      ),
    ),
    'locale::parselocale' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
        'locale' => 'string',
      ),
    ),
    'mb_check_encoding' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'value' => 'array<array-key, mixed>|string',
        'encoding=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'array<array-key, mixed>|null|string',
        'encoding=' => 'null|string',
      ),
    ),
    'messageformatter::create' => 
    array (
      'old' => 
      array (
        0 => 'MessageFormatter',
        'locale' => 'string',
        'pattern' => 'string',
      ),
      'new' => 
      array (
        0 => 'MessageFormatter|null',
        'locale' => 'string',
        'pattern' => 'string',
      ),
    ),
    'mysqli::get_charset' => 
    array (
      'old' => 
      array (
        0 => 'object',
      ),
      'new' => 
      array (
        0 => 'null|object',
      ),
    ),
    'norewinditerator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'numberformatter::format' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'num' => 'mixed',
        'type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'num' => 'float|int',
        'type=' => 'int',
      ),
    ),
    'odbc_fetch_array' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'statement' => 'resource',
        'row=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'statement' => 'resource',
        'row=' => 'int|null',
      ),
    ),
    'odbc_fetch_into' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'statement' => 'resource',
        '&w_array' => 'array<array-key, mixed>',
        'row=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'statement' => 'resource',
        '&w_array' => 'array<array-key, mixed>',
        'row=' => 'int|null',
      ),
    ),
    'odbc_fetch_object' => 
    array (
      'old' => 
      array (
        0 => 'false|stdClass',
        'statement' => 'resource',
        'row=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|stdClass',
        'statement' => 'resource',
        'row=' => 'int|null',
      ),
    ),
    'outeriterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'recursivecachingiterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'recursivecallbackfilteriterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'recursivefilteriterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'recursiveregexiterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'reflectionclass::newinstanceargs' => 
    array (
      'old' => 
      array (
        0 => 'object',
        'args=' => 'array<int<0, max>|string, mixed>',
      ),
      'new' => 
      array (
        0 => 'null|object',
        'args=' => 'array<int<0, max>|string, mixed>',
      ),
    ),
    'reflectionfunction::getclosurescopeclass' => 
    array (
      'old' => 
      array (
        0 => 'ReflectionClass',
      ),
      'new' => 
      array (
        0 => 'ReflectionClass|null',
      ),
    ),
    'reflectionfunction::getclosurethis' => 
    array (
      'old' => 
      array (
        0 => 'object',
      ),
      'new' => 
      array (
        0 => 'null|object',
      ),
    ),
    'reflectionmethod::getclosurescopeclass' => 
    array (
      'old' => 
      array (
        0 => 'ReflectionClass',
      ),
      'new' => 
      array (
        0 => 'ReflectionClass|null',
      ),
    ),
    'reflectionmethod::getclosurethis' => 
    array (
      'old' => 
      array (
        0 => 'object',
      ),
      'new' => 
      array (
        0 => 'null|object',
      ),
    ),
    'reflectionobject::newinstanceargs' => 
    array (
      'old' => 
      array (
        0 => 'object',
        'args=' => 'array<int<0, max>|string, mixed>',
      ),
      'new' => 
      array (
        0 => 'null|object',
        'args=' => 'array<int<0, max>|string, mixed>',
      ),
    ),
    'regexiterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'session_set_save_handler' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'open' => 'callable(string, string):bool',
        'close' => 'callable():bool',
        'read' => 'callable(string):string',
        'write' => 'callable(string, string):bool',
        'destroy' => 'callable(string):bool',
        'gc' => 'callable(string):bool',
        'create_sid=' => 'callable():string',
        'validate_sid=' => 'callable(string):bool',
        'update_timestamp=' => 'callable(string):bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'open' => 'callable(string, string):bool',
        'close' => 'callable():bool',
        'read' => 'callable(string):string',
        'write' => 'callable(string, string):bool',
        'destroy' => 'callable(string):bool',
        'gc' => 'callable(string):bool',
        'create_sid=' => 'callable():string|null',
        'validate_sid=' => 'callable(string):bool|null',
        'update_timestamp=' => 'callable(string):bool|null',
      ),
    ),
    'soapclient::__setlocation' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'new_location=' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'new_location=' => 'string',
      ),
    ),
    'soapclient::__soapcall' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'function_name' => 'string',
        'arguments' => 'array<array-key, mixed>',
        'options=' => 'array<array-key, mixed>',
        'input_headers=' => 'SoapHeader|array<array-key, mixed>',
        '&w_output_headers=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'function_name' => 'string',
        'arguments' => 'array<array-key, mixed>',
        'options=' => 'array<array-key, mixed>|null',
        'input_headers=' => 'SoapHeader|array<array-key, mixed>',
        '&w_output_headers=' => 'array<array-key, mixed>',
      ),
    ),
    'soapheader::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'namespace' => 'string',
        'name' => 'string',
        'data=' => 'mixed',
        'mustunderstand=' => 'bool',
        'actor=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'namespace' => 'string',
        'name' => 'string',
        'data=' => 'mixed',
        'mustunderstand=' => 'bool',
        'actor=' => 'null|string',
      ),
    ),
    'soapvar::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'data' => 'mixed',
        'encoding' => 'int',
        'type_name=' => 'null|string',
        'type_namespace=' => 'null|string',
        'node_name=' => 'null|string',
        'node_namespace=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'mixed',
        'encoding' => 'int|null',
        'type_name=' => 'null|string',
        'type_namespace=' => 'null|string',
        'node_name=' => 'null|string',
        'node_namespace=' => 'null|string',
      ),
    ),
    'splfileobject::fscanf' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|int',
        'format' => 'string',
        '&...w_vars=' => 'float|int|string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|int|null',
        'format' => 'string',
        '&...w_vars=' => 'float|int|string',
      ),
    ),
    'spltempfileobject::fscanf' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|int',
        'format' => 'string',
        '&...w_vars=' => 'float|int|string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|int|null',
        'format' => 'string',
        '&...w_vars=' => 'float|int|string',
      ),
    ),
    'strtok' => 
    array (
      'old' => 
      array (
        0 => 'false|non-empty-string',
        'string' => 'string',
        'token' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|non-empty-string',
        'string' => 'string',
        'token' => 'null|string',
      ),
    ),
    'strtr' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'from' => 'string',
        'to' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'from' => 'string',
        'to' => 'null|string',
      ),
    ),
    'version_compare' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'version1' => 'string',
        'version2' => 'string',
        'operator' => '\'!=\'|\'<\'|\'<=\'|\'<>\'|\'=\'|\'==\'|\'>\'|\'>=\'|\'eq\'|\'ge\'|\'gt\'|\'le\'|\'lt\'|\'ne\'',
      ),
      'new' => 
      array (
        0 => 'bool',
        'version1' => 'string',
        'version2' => 'string',
        'operator' => '\'!=\'|\'<\'|\'<=\'|\'<>\'|\'=\'|\'==\'|\'>\'|\'>=\'|\'eq\'|\'ge\'|\'gt\'|\'le\'|\'lt\'|\'ne\'|null',
      ),
    ),
    'xml_set_default_handler' => 
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
    'xml_set_end_namespace_decl_handler' => 
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
    'xml_set_external_entity_ref_handler' => 
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
    'xml_set_notation_decl_handler' => 
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
    'xml_set_unparsed_entity_decl_handler' => 
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
    'xsltprocessor::setparameter' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'namespace' => 'string',
        'name' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'namespace' => 'string',
        'name' => 'string',
        'value' => 'null|string',
      ),
    ),
    'xsltprocessor::transformtoxml' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'document' => 'DOMDocument',
      ),
      'new' => 
      array (
        0 => 'false|null|string',
        'document' => 'DOMDocument',
      ),
    ),
  ),
);