<?php
namespace Psalm\Internal;

/**
 * Stolen from https://github.com/etsy/phan/blob/master/src/Phan/Language/Internal/PropertyMap.php
 *
 * The MIT License (MIT)
 * Copyright (c) 2015 Rasmus Lerdorf
 * Copyright (c) 2015 Andrew Morrison
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

return [
    'arrayiterator' => [
        'name' => 'string',
    ],
    'arrayobject' => [
        'name' => 'string',
    ],
    'collator' => [
        'name' => 'string',
    ],
    'dateinterval' => [
        'd' => 'integer',
        'days' => 'false|int',
        'f' => 'float',
        'h' => 'integer',
        'i' => 'integer',
        'invert' => 'integer',
        'm' => 'integer',
        's' => 'integer',
        'y' => 'integer',
    ],
    'directory' => [
        'handle' => 'resource',
        'path' => 'string',
    ],
    'directoryiterator' => [
        'name' => 'string',
    ],
    'domattr' => [
        'name' => 'string',
        'ownerelement' => 'DOMElement',
        'schematypeinfo' => 'bool',
        'specified' => 'bool',
        'value' => 'string',
    ],
    'domcharacterdata' => [
        'data' => 'string',
        'length' => 'int',
    ],
    'domdocument' => [
        'actualencoding' => 'string',
        'childelementcount' => 'int',
        'config' => 'null',
        'doctype' => 'DOMDocumentType',
        'documentelement' => 'DOMElement',
        'documenturi' => 'string',
        'encoding' => 'string',
        'firstelementchild' => 'DOMElement|null',
        'formatoutput' => 'bool',
        'implementation' => 'DOMImplementation',
        'lastelementchild' => 'DOMElement|null',
        'ownerdocument' => 'null',
        'parentnode' => 'null',
        'preservewhitespace' => 'bool',
        'recover' => 'bool',
        'resolveexternals' => 'bool',
        'standalone' => 'bool',
        'stricterrorchecking' => 'bool',
        'substituteentities' => 'bool',
        'validateonparse' => 'bool',
        'version' => 'string',
        'xmlencoding' => 'string',
        'xmlstandalone' => 'bool',
        'xmlversion' => 'string',
    ],
    'domdocumentfragment' => [
        'name' => 'string',
    ],
    'domdocumenttype' => [
        'entities' => 'DOMNamedNodeMap',
        'internalsubset' => 'string',
        'name' => 'string',
        'notations' => 'DOMNamedNodeMap',
        'publicid' => 'string',
        'systemid' => 'string',
    ],
    'domelement' => [
        'attributes' => 'DOMNamedNodeMap<DOMAttr>',
        'childelementcount' => 'int',
        'firstelementchild' => 'DOMElement|null',
        'lastelementchild' => 'DOMElement|null',
        'nextelementsibling' => 'DOMElement|null',
        'previouselementsibling' => 'DOMElement|null',
        'schematypeinfo' => 'bool',
        'tagname' => 'string',
    ],
    'domentity' => [
        'actualencoding' => 'string',
        'encoding' => 'string',
        'notationname' => 'string',
        'publicid' => 'string',
        'systemid' => 'string',
        'version' => 'string',
    ],
    'domentityreference' => [
        'name' => 'string',
    ],
    'domexception' => [
        'code' => 'int',
    ],
    'domimplementation' => [
        'name' => 'string',
    ],
    'domnamednodemap' => [
        'length' => 'int',
    ],
    'domnode' => [
        'attributes' => 'null',
        'baseuri' => 'string|null',
        'childnodes' => 'DomNodeList<DomNode>',
        'firstchild' => 'DOMNode|null',
        'lastchild' => 'DOMNode|null',
        'localname' => 'string',
        'namespaceuri' => 'string|null',
        'nextsibling' => 'DOMNode|null',
        'nodename' => 'string',
        'nodetype' => 'int',
        'nodevalue' => 'string|null',
        'ownerdocument' => 'DOMDocument|null',
        'parentnode' => 'DOMNode|null',
        'prefix' => 'string',
        'previoussibling' => 'DOMNode|null',
        'textcontent' => 'string',
    ],
    'domnodelist' => [
        'length' => 'int',
    ],
    'domnotation' => [
        'publicid' => 'string',
        'systemid' => 'string',
    ],
    'domprocessinginstruction' => [
        'data' => 'string',
        'target' => 'string',
    ],
    'domtext' => [
        'wholetext' => 'string',
    ],
    'domxpath' => [
        'document' => 'DOMDocument',
    ],
    'error' => [
        'code' => 'int',
        'file' => 'string',
        'line' => 'int',
        'message' => 'string',
    ],
    'errorexception' => [
        'severity' => 'int',
    ],
    'event' => [
        'pending' => 'bool',
    ],
    'eventbuffer' => [
        'contiguous-space' => 'int',
        'length' => 'int',
    ],
    'eventbufferevent' => [
        'fd' => 'integer',
        'input' => 'EventBuffer',
        'output' => 'EventBuffer',
        'priority' => 'integer',
    ],
    'eventlistener' => [
        'fd' => 'int',
    ],
    'eventsslcontext' => [
        'local-cert' => 'string',
        'local-pk' => 'string',
    ],
    'exception' => [
        'code' => 'int',
        'file' => 'string',
        'line' => 'int',
        'message' => 'string',
    ],
    'filteriterator' => [
        'name' => 'string',
    ],
    'libxmlerror' => [
        'code' => 'int',
        'column' => 'int',
        'file' => 'string',
        'level' => 'int',
        'line' => 'int',
        'message' => 'string',
    ],
    'limititerator' => [
        'name' => 'string',
    ],
    'locale' => [
        'name' => 'string',
    ],
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
    'mysqli' => [
        'affected_rows' => 'int',
        'client_info' => 'string',
        'client_version' => 'int',
        'connect_errno' => 'int',
        'connect_error' => '?string',
        'errno' => 'int',
        'error' => 'string',
        'error_list' => 'array',
        'field_count' => 'int',
        'host_info' => 'string',
        'info' => 'string',
        'insert_id' => 'int|string',
        'protocol_version' => 'string',
        'server_info' => 'string',
        'server_version' => 'int',
        'sqlstate' => 'string',
        'thread_id' => 'int',
        'warning_count' => 'int',
    ],
    'mysqli_driver' => [
        'client_info' => 'string',
        'client_version' => 'string',
        'driver_version' => 'string',
        'embedded' => 'string',
        'reconnect' => 'bool',
        'report_mode' => 'int',
    ],
    'mysqli_result' => [
        'current_field' => 'int',
        'field_count' => 'int',
        'lengths' => 'array|null',
        'num_rows' => 'int',
        'type' => 'mixed',
    ],
    'mysqli_sql_exception' => [
        'sqlstate' => 'string',
    ],
    'mysqli_stmt' => [
        'affected_rows' => 'int',
        'errno' => 'int',
        'error' => 'string',
        'error_list' => 'array',
        'field_count' => 'int',
        'id' => 'mixed',
        'insert_id' => 'int',
        'num_rows' => 'int',
        'param_count' => 'int',
        'sqlstate' => 'string',
    ],
    'mysqli_warning' => [
        'errno' => 'int',
        'message' => 'string',
        'sqlstate' => 'string',
    ],
    'norewinditerator' => [
        'name' => 'string',
    ],
    'normalizer' => [
        'name' => 'string',
    ],
    'numberformatter' => [
        'name' => 'string',
    ],
    'parentiterator' => [
        'name' => 'string',
    ],
    'pdoexception' => [
        'code' => 'int|string',
        'errorinfo' => 'array',
    ],
    'pdostatement' => [
        'querystring' => 'string',
    ],
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
    'recursivearrayiterator' => [
        'name' => 'string',
    ],
    'recursivecachingiterator' => [
        'name' => 'string',
    ],
    'recursivedirectoryiterator' => [
        'name' => 'string',
    ],
    'recursiveregexiterator' => [
        'name' => 'string',
    ],
    'reflectionclass' => [
        'name' => 'string',
    ],
    'reflectionmethod' => [
        'class' => 'string',
        'name' => 'string',
    ],
    'reflectionparameter' => [
        'name' => 'string',
    ],
    'regexiterator' => [
        'name' => 'string',
    ],
    'simplexmliterator' => [
        'name' => 'string',
    ],
    'snmp' => [
        'enum-print' => 'bool',
        'exceptions-enabled' => 'int',
        'info' => 'array',
        'max-oids' => 'int',
        'oid-increasing-check' => 'bool',
        'oid-output-format' => 'int',
        'quick-print' => 'bool',
        'valueretrieval' => 'int',
    ],
    'snmpexception' => [
        'code' => 'string',
    ],
    'soapfault' => [
        '_name' => 'string',
        'detail' => 'mixed|null',
        'faultactor' => 'string|null',
        'faultcode' => 'string|null',
        'faultcodens' => 'string|null',
        'faultstring' => 'string',
        'headerfault' => 'mixed|null',
    ],
    'solrdocumentfield' => [
        'boost' => 'float',
        'name' => 'string',
        'values' => 'array',
    ],
    'solrexception' => [
        'sourcefile' => 'string',
        'sourceline' => 'integer',
        'zif-name' => 'string',
    ],
    'solrresponse' => [
        'http-digested-response' => 'string',
        'http-raw-request' => 'string',
        'http-raw-request-headers' => 'string',
        'http-raw-response' => 'string',
        'http-raw-response-headers' => 'string',
        'http-request-url' => 'string',
        'http-status' => 'integer',
        'http-status-message' => 'string',
        'parser-mode' => 'integer',
        'success' => 'bool',
    ],
    'spldoublylinkedlist' => [
        'name' => 'string',
    ],
    'splheap' => [
        'name' => 'string',
    ],
    'splmaxheap' => [
        'name' => 'string',
    ],
    'splminheap' => [
        'name' => 'string',
    ],
    'splpriorityqueue' => [
        'name' => 'string',
    ],
    'splqueue' => [
        'name' => 'string',
    ],
    'splstack' => [
        'name' => 'string',
    ],
    'streamwrapper' => [
        'context' => 'resource',
    ],
    'tidy' => [
        'errorbuffer' => 'string',
    ],
    'tidynode' => [
        'attribute' => 'array',
        'child' => '?array',
        'column' => 'int',
        'id' => 'int',
        'line' => 'int',
        'name' => 'string',
        'proprietary' => 'bool',
        'type' => 'int',
        'value' => 'string',
    ],
    'tokyotyrantexception' => [
        'code' => 'int',
    ],
    'xmlreader' => [
        'attributecount' => 'int',
        'baseuri' => 'string',
        'depth' => 'int',
        'hasattributes' => 'bool',
        'hasvalue' => 'bool',
        'isdefault' => 'bool',
        'isemptyelement' => 'bool',
        'localname' => 'string',
        'name' => 'string',
        'namespaceuri' => 'string',
        'nodetype' => 'int',
        'prefix' => 'string',
        'value' => 'string',
        'xmllang' => 'string',
    ],
    'ziparchive' => [
        'comment' => 'string',
        'filename' => 'string',
        'numfiles' => 'int',
        'status' => 'int',
        'statussys' => 'int',
    ],
];
