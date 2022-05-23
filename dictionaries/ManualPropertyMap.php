<?php
namespace Psalm\Internal;

/**
 * This file holds manually defined property maps, which are not added to the
 * official PHP docs and therefore can not be automatically updated by
 * bin/update-property-map.php.
 *
 * If you change this file, please run bin/update-property-map.php to keep
 * PropertyMap.php in sync.
 */

return [
    //
    // Incorrectly documented classes from here on.
    // Revise these against the current state of the docs from time to time.
    //
    'dateinterval' => [
        // documented as 'mixed' in doc-en/reference/datetime/dateinterval.xml:90.
        'days' => 'false|int',
    ],
    'domnode' => [
        // documented as 'DomNodeList' in doc-en/reference/dom/domnode.xml:57.
        'childnodes' => 'DomNodeList<DomNode>'
    ],
    'tidy' => [
        // documented via <xi:include> in doc-en/reference/tidy/tidy.xml:33
        'errorbuffer' => 'string',
    ],
    //
    // Undocumented classes from here on.
    //
    'phpparser\\node\\expr\\array_' => [
        'items' => 'array<int, PhpParser\\Node\\Expr\\ArrayItem|null>',
    ],
    'phpparser\\node\\expr\\arrowfunction' => [
        'params' => 'list<PhpParser\\Node\\Param>',
    ],
    'phpparser\\node\\expr\\closure' => [
        'params' => 'list<PhpParser\\Node\\Param>',
    ],
    'phpparser\\node\\expr\\list_' => [
        'items' => 'array<int, PhpParser\\Node\\Expr\\ArrayItem|null>',
    ],
    'phpparser\\node\\expr\\shellexec' => [
        'parts' => 'list<PhpParser\\Node>',
    ],
    'phpparser\\node\\matcharm' => [
        'conds' => 'null|non-empty-list<PhpParser\\Node\\Expr>',
    ],
    'phpparser\\node\\name' => [
        'parts' => 'non-empty-list<non-empty-string>',
    ],
    'phpparser\\node\\stmt\\case_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'phpparser\\node\\stmt\\catch_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'phpparser\\node\\stmt\\class_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'phpparser\\node\\stmt\\do_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'phpparser\\node\\stmt\\else_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'phpparser\\node\\stmt\\elseif_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'phpparser\\node\\stmt\\finally_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'phpparser\\node\\stmt\\for_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'phpparser\\node\\stmt\\foreach_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'phpparser\\node\\stmt\\if_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'phpparser\\node\\stmt\\interface_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'phpparser\\node\\stmt\\namespace_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'phpparser\\node\\stmt\\trait_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'phpparser\\node\\stmt\\trycatch' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'phpparser\\node\\stmt\\while_' => [
        'stmts' => 'list<PhpParser\\Node\\Stmt>',
    ],
    'rdkafka\\message' => [
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
    'mongoclient' => [
        'connected' => 'boolean',
        'status' => 'string',
    ],
    'mongocollection' => [
        'db' => 'MongoDB',
        'w' => 'integer',
        'wtimeout' => 'integer',
    ],
    'mongocursor' => [
        'slaveokay' => 'boolean',
        'timeout' => 'integer',
    ],
    'mongodb' => [
        'w' => 'integer',
        'wtimeout' => 'integer',
    ],
    'mongodb-driver-exception-writeexception' => [
        'writeresult' => 'MongoDBDriverWriteResult',
    ],
    'mongoid' => [
        'id' => 'string',
    ],
    'mongoint32' => [
        'value' => 'string',
    ],
    'mongoint64' => [
        'value' => 'string',
    ],
    'tokyotyrantexception' => [
        'code' => 'int',
    ],
];
