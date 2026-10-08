<?php

declare(strict_types=1);

use Psalm\Type\TaintKind;

/**
 * The builtin functions that read data from outside the program unconditionally: what they return
 * (`return`), or what they write to a by-reference parameter (keyed by its name), is a taint source.
 *
 * The reads that relay the taint of the stream they are given (fgets(), fread(), ...) carry
 * `@psalm-flow` annotations in the stubs instead, and the network streams opened here are
 * sources, so reading from them is too.
 *
 * @var array<lowercase-string, non-empty-array<string, int-mask-of<TaintKind::*>>>
 */
return [
    // sockets
    'socket_read' => ['return' => TaintKind::ALL_INPUT],
    'socket_recv' => ['data' => TaintKind::ALL_INPUT],
    'socket_recvfrom' => ['data' => TaintKind::ALL_INPUT],
    'socket_recvmsg' => ['message' => TaintKind::ALL_INPUT],
    // network streams
    'fsockopen' => ['return' => TaintKind::ALL_INPUT],
    'pfsockopen' => ['return' => TaintKind::ALL_INPUT],
    'stream_socket_client' => ['return' => TaintKind::ALL_INPUT],
    'stream_socket_accept' => ['return' => TaintKind::ALL_INPUT],
    'stream_socket_recvfrom' => ['return' => TaintKind::ALL_INPUT],
    'socket_export_stream' => ['return' => TaintKind::ALL_INPUT],
    // dns answers: whoever runs the name servers of a domain chooses them
    'dns_get_record' => ['return' => TaintKind::ALL_INPUT],
    'getmxrr' => ['hosts' => TaintKind::ALL_INPUT],
    'gethostbyaddr' => ['return' => TaintKind::ALL_INPUT],
    // http clients
    'curl_exec' => ['return' => TaintKind::ALL_INPUT],
    'curl_multi_getcontent' => ['return' => TaintKind::ALL_INPUT],
    // command-line options: whoever runs the script chooses them, as with $argv
    'getopt' => ['return' => TaintKind::ALL_INPUT],
];
