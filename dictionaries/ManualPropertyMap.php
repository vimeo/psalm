<?php
namespace Psalm\Internal;

/**
 * This file holds manually defined property maps, which are not added to the
 * official PHP docs and therefore can not be automatically updated by
 * bin/stubs/update-property-map.php.
 *
 * Names are resolved case-sensitively: class and property names must use their canonical (declared) casing.
 *
 * If you change this file, please run bin/stubs/update-property-map.php to keep
 * PropertyMap.php in sync.
 */

return [
    //
    // Incorrectly documented classes from here on.
    // Revise these against the current state of the docs from time to time.
    //
    'DateInterval' => [
        // documented as 'mixed' in doc-en/reference/datetime/dateinterval.xml:90.
        'days' => 'false|int',
    ],
    'DOMNode' => [
        // documented as 'DomNodeList' in doc-en/reference/dom/domnode.xml:57.
        'childNodes' => 'DOMNodeList<DOMNode>'
    ],
    'tidy' => [
        // documented via <xi:include> in doc-en/reference/tidy/tidy.xml:33
        'errorBuffer' => 'string',
    ],
    //
    // Undocumented classes from here on.
    //
    'PhpParser\\Node\\Expr\\Array_' => [
        'items' => 'array<int, PhpParser\\Node\\Expr\\ArrayItem|null>',
    ],
    'PhpParser\\Node\\Expr\\ArrowFunction' => [
        'params' => 'list<PhpParser\\Node\\Param>',
    ],
    'PhpParser\\Node\\Expr\\Closure' => [
        'params' => 'list<PhpParser\\Node\\Param>',
    ],
    'PhpParser\\Node\\Expr\\List_' => [
        'items' => 'array<int, PhpParser\\Node\\Expr\\ArrayItem|null>',
    ],
    'PhpParser\\Node\\Expr\\ShellExec' => [
        'parts' => 'list<PhpParser\\Node>',
    ],
    'PhpParser\\Node\\MatchArm' => [
        'conds' => 'null|non-empty-list<PhpParser\\Node\\Expr>',
    ],
    'PhpParser\\Node\\Name' => [
        'parts' => 'non-empty-list<non-empty-string>',
    ],
    'PhpParser\\Node\\Stmt\\Case_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'PhpParser\\Node\\Stmt\\Catch_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'PhpParser\\Node\\Stmt\\Class_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'PhpParser\\Node\\Stmt\\Do_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'PhpParser\\Node\\Stmt\\Else_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'PhpParser\\Node\\Stmt\\ElseIf_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'PhpParser\\Node\\Stmt\\Finally_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'PhpParser\\Node\\Stmt\\For_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'PhpParser\\Node\\Stmt\\Foreach_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'PhpParser\\Node\\Stmt\\If_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'PhpParser\\Node\\Stmt\\Interface_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'PhpParser\\Node\\Stmt\\Namespace_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'PhpParser\\Node\\Stmt\\Trait_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'PhpParser\\Node\\Stmt\\TryCatch' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'PhpParser\\Node\\Stmt\\While_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'RdKafka\\Message' => [
        'err' => 'int',
        'headers' => 'array<string, string>|null',
        'key' => 'string|null',
        'offset' => 'int',
        'partition' => 'int',
        'payload' => 'string',
        'timestamp' => 'int',
        'topic_name' => 'string',
    ],

    //
    // Legacy extensions that got removed.
    //
    'MongoClient' => [
        'connected' => 'boolean',
        'status' => 'string',
    ],
    'MongoCollection' => [
        'db' => 'MongoDB',
        'w' => 'integer',
        'wtimeout' => 'integer',
    ],
    'MongoCursor' => [
        'slaveOkay' => 'boolean',
        'timeout' => 'integer',
    ],
    'MongoDB' => [
        'w' => 'integer',
        'wtimeout' => 'integer',
    ],
    'mongodb-driver-exception-writeexception' => [
        'writeresult' => 'MongoDBDriverWriteResult',
    ],
    'MongoId' => [
        'id' => 'string',
    ],
    'MongoInt32' => [
        'value' => 'string',
    ],
    'MongoInt64' => [
        'value' => 'string',
    ],
    'TokyoTyrantException' => [
        'code' => 'int',
    ],
];
