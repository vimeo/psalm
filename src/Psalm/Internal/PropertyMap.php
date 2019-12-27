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
    'arrayobject' => [
        'name' => 'string',
    ],
    'limititerator' => [
        'name' => 'string',
    ],
    'solrdocumentfield' => [
        'name' => 'string',
        'boost' => 'float',
        'values' => 'array',
    ],
    'domprocessinginstruction' => [
        'target' => 'string',
        'data' => 'string',
    ],
    'recursivearrayiterator' => [
        'name' => 'string',
    ],
    'eventbuffer' => [
        'length' => 'int',
        'contiguous-space' => 'int',
    ],
    'mongocursor' => [
        'slaveokay' => 'boolean',
        'timeout' => 'integer',
    ],
    'domxpath' => [
        'document' => 'DOMDocument',
    ],
    'domentity' => [
        'publicId' => 'string',
        'systemId' => 'string',
        'notationName' => 'string',
        'actualEncoding' => 'string',
        'encoding' => 'string',
        'version' => 'string',
    ],
    'splminheap' => [
        'name' => 'string',
    ],
    'mongodb-driver-exception-writeexception' => [
        'writeresult' => 'MongoDBDriverWriteResult',
    ],
    'ziparchive' => [
        'status' => 'int',
        'statusSys' => 'int',
        'numFiles' => 'int',
        'filename' => 'string',
        'comment' => 'string',
    ],
    'solrexception' => [
        'sourceline' => 'integer',
        'sourcefile' => 'string',
        'zif-name' => 'string',
    ],
    'arrayiterator' => [
        'name' => 'string',
    ],
    'mongoid' => [
        'id' => 'string',
    ],
    'dateinterval' => [
        'y' => 'integer',
        'm' => 'integer',
        'd' => 'integer',
        'h' => 'integer',
        'i' => 'integer',
        's' => 'integer',
        'f' => 'float', // only present from 7.1 onwards
        'invert' => 'integer',
        'days' => 'mixed',
    ],
    'tokyotyrantexception' => [
        'code' => 'int',
    ],
    'tidy' => [
        'errorbuffer' => 'string',
    ],
    'filteriterator' => [
        'name' => 'string',
    ],
    'parentiterator' => [
        'name' => 'string',
    ],
    'recursiveregexiterator' => [
        'name' => 'string',
    ],
    'error' => [
        'message' => 'string',
        'code' => 'int',
        'file' => 'string',
        'line' => 'int',
    ],
    'domexception' => [
        'code' => 'int',
    ],
    'domentityreference' => [
        'name' => 'string',
    ],
    'spldoublylinkedlist' => [
        'name' => 'string',
    ],
    'domdocumentfragment' => [
        'name' => 'string',
    ],
    'collator' => [
        'name' => 'string',
    ],
    'streamwrapper' => [
        'context' => 'resource',
    ],
    'pdostatement' => [
        'querystring' => 'string',
    ],
    'domnotation' => [
        'publicId' => 'string',
        'systemId' => 'string',
    ],
    'snmpexception' => [
        'code' => 'string',
    ],
    'directoryiterator' => [
        'name' => 'string',
    ],
    'splqueue' => [
        'name' => 'string',
    ],
    'locale' => [
        'name' => 'string',
    ],
    'directory' => [
        'path' => 'string',
        'handle' => 'resource',
    ],
    'splheap' => [
        'name' => 'string',
    ],
    'domnodelist' => [
        'length' => 'int',
    ],
    'mongodb' => [
        'w' => 'integer',
        'wtimeout' => 'integer',
    ],
    'splpriorityqueue' => [
        'name' => 'string',
    ],
    'mongoclient' => [
        'connected' => 'boolean',
        'status' => 'string',
    ],
    'domdocument' => [
        'actualEncoding' => 'string',
        'config' => 'null',
        'doctype' => 'DOMDocumentType',
        'documentElement' => 'DOMElement',
        'documentURI' => 'string',
        'encoding' => 'string',
        'formatOutput' => 'bool',
        'implementation' => 'DOMImplementation',
        'preserveWhiteSpace' => 'bool',
        'recover' => 'bool',
        'resolveExternals' => 'bool',
        'standalone' => 'bool',
        'strictErrorChecking' => 'bool',
        'substituteEntities' => 'bool',
        'validateOnParse' => 'bool',
        'version' => 'string',
        'xmlEncoding' => 'string',
        'xmlStandalone' => 'bool',
        'xmlVersion' => 'string',
        'ownerDocument' => 'null',
        'parentNode' => 'null',
    ],
    'libxmlerror' => [
        'level' => 'int',
        'code' => 'int',
        'column' => 'int',
        'message' => 'string',
        'file' => 'string',
        'line' => 'int',
    ],
    'domimplementation' => [
        'name' => 'string',
    ],
    'normalizer' => [
        'name' => 'string',
    ],
    'mysqli-driver' => [
        'client-info' => 'string',
        'client-version' => 'string',
        'driver-version' => 'string',
        'embedded' => 'string',
        'reconnect' => 'bool',
        'report-mode' => 'int',
    ],
    'norewinditerator' => [
        'name' => 'string',
    ],
    'event' => [
        'pending' => 'bool',
    ],
    'domdocumenttype' => [
        'publicId' => 'string',
        'systemId' => 'string',
        'name' => 'string',
        'entities' => 'DOMNamedNodeMap',
        'notations' => 'DOMNamedNodeMap',
        'internalSubset' => 'string',
    ],
    'errorexception' => [
        'severity' => 'int',
    ],
    'recursivedirectoryiterator' => [
        'name' => 'string',
    ],
    'domcharacterdata' => [
        'data' => 'string',
        'length' => 'int',
    ],
    'mongocollection' => [
        'db' => 'MongoDB',
        'w' => 'integer',
        'wtimeout' => 'integer',
    ],
    'mongoint64' => [
        'value' => 'string',
    ],
    'eventlistener' => [
        'fd' => 'int',
    ],
    'splmaxheap' => [
        'name' => 'string',
    ],
    'regexiterator' => [
        'name' => 'string',
    ],
    'domelement' => [
        'schemaTypeInfo' => 'bool',
        'tagName' => 'string',
        'attributes' => 'DOMNamedNodeMap',
    ],
    'tidynode' => [
        'value' => 'string',
        'name' => 'string',
        'type' => 'int',
        'line' => 'int',
        'column' => 'int',
        'proprietary' => 'bool',
        'id' => 'int',
        'attribute' => 'array',
        'child' => 'array',
    ],
    'recursivecachingiterator' => [
        'name' => 'string',
    ],
    'solrresponse' => [
        'http-status' => 'integer',
        'parser-mode' => 'integer',
        'success' => 'bool',
        'http-status-message' => 'string',
        'http-request-url' => 'string',
        'http-raw-request-headers' => 'string',
        'http-raw-request' => 'string',
        'http-raw-response-headers' => 'string',
        'http-raw-response' => 'string',
        'http-digested-response' => 'string',
    ],
    'domnamednodemap' => [
        'length' => 'int',
    ],
    'mysqli-sql-exception' => [
        'sqlstate' => 'string',
    ],
    'splstack' => [
        'name' => 'string',
    ],
    'numberformatter' => [
        'name' => 'string',
    ],
    'eventsslcontext' => [
        'local-cert' => 'string',
        'local-pk' => 'string',
    ],
    'pdoexception' => [
        'errorinfo' => 'array',
        'code' => 'string',
    ],
    'domnode' => [
        'nodeName' => 'string',
        'nodeValue' => 'string',
        'nodeType' => 'int',
        'parentNode' => 'DOMNode',
        'childNodes' => 'DOMNodeList',
        'firstChild' => 'DOMNode|null',
        'lastChild' => 'DOMNode|null',
        'previousSibling' => 'DOMNode|null',
        'nextSibling' => 'DOMNode|null',
        'attributes' => 'null',
        'ownerDocument' => 'DOMDocument',
        'namespaceURI' => 'string|null',
        'prefix' => 'string',
        'localName' => 'string',
        'baseURI' => 'string|null',
        'textContent' => 'string',
    ],
    'domattr' => [
        'name' => 'string',
        'ownerElement' => 'DOMElement',
        'schemaTypeInfo' => 'bool',
        'specified' => 'bool',
        'value' => 'string',
    ],
    'simplexmliterator' => [
        'name' => 'string',
    ],
    'snmp' => [
        'max-oids' => 'int',
        'valueretrieval' => 'int',
        'quick-print' => 'bool',
        'enum-print' => 'bool',
        'oid-output-format' => 'int',
        'oid-increasing-check' => 'bool',
        'exceptions-enabled' => 'int',
        'info' => 'array',
    ],
    'mongoint32' => [
        'value' => 'string',
    ],
    'xmlreader' => [
        'attributeCount' => 'int',
        'baseURI' => 'string',
        'depth' => 'int',
        'hasAttributes' => 'bool',
        'hasValue' => 'bool',
        'isDefault' => 'bool',
        'isEmptyElement' => 'bool',
        'localName' => 'string',
        'name' => 'string',
        'namespaceURI' => 'string',
        'nodeType' => 'int',
        'prefix' => 'string',
        'value' => 'string',
        'xmlLang' => 'string',
    ],
    'eventbufferevent' => [
        'fd' => 'integer',
        'priority' => 'integer',
        'input' => 'EventBuffer',
        'output' => 'EventBuffer',
    ],
    'domtext' => [
        'wholeText' => 'string',
    ],
    'exception' => [
        'message' => 'string',
        'code' => 'int',
        'file' => 'string',
        'line' => 'int',
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
    'phpparser\\node\\expr\\funccall' => [
        'args' => 'array<int, PhpParser\Node\Arg>',
    ],
    'phpparser\\node\\expr\\new_' => [
        'args' => 'array<int, PhpParser\Node\Arg>',
    ],
    'phpparser\\node\\expr\\array_' => [
        'items' => 'array<int, PhpParser\Node\Expr\ArrayItem|null>',
    ],
    'phpparser\node\expr\list_' => [
        'items' => 'array<int, PhpParser\Node\Expr\ArrayItem|null>',
    ],
    'phpparser\\node\\expr\\methodcall' => [
        'args' => 'array<int, PhpParser\Node\Arg>',
    ],
    'phpparser\\node\\expr\\staticcall' => [
        'args' => 'array<int, PhpParser\Node\Arg>',
    ],
    'phpparser\\node\\stmt\\namespace_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\if_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\elseif_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\else_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\for_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\foreach_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\trycatch' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\catch_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\finally_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\case_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\while_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\do_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\class_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\trait_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
    'phpparser\\node\\stmt\\interface_' => [
        'stmts' => 'array<int, PhpParser\Node\Stmt>',
    ],
];
