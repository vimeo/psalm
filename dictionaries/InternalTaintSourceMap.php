<?php

declare(strict_types=1);

/**
 * The builtin functions that read data from outside the program unconditionally: what they return
 * (`return`), or what they write to the by-reference parameters named, is user input (see
 * Codebase::getInputTaints()).
 *
 * The reads that relay the taint of the stream they are given (fgets(), fread(), ...) carry
 * `@psalm-flow` annotations in the stubs instead, and the network streams opened here are
 * sources, so reading from them is too.
 *
 * @var array<lowercase-string, non-empty-list<non-empty-string>>
 */
return [
    // sockets
    'socket_read' => ['return'],
    'socket_recv' => ['data'],
    'socket_recvfrom' => ['data'],
    'socket_recvmsg' => ['message'],
    // network streams
    'fsockopen' => ['return'],
    'pfsockopen' => ['return'],
    'stream_socket_client' => ['return'],
    'stream_socket_accept' => ['return'],
    'stream_socket_recvfrom' => ['return'],
    'socket_export_stream' => ['return'],
    // dns answers: whoever runs the name servers of a domain chooses them
    'dns_get_record' => ['return'],
    'getmxrr' => ['hosts'],
    'gethostbyaddr' => ['return'],
    // http clients
    'curl_exec' => ['return'],
    'curl_multi_getcontent' => ['return'],
    // command-line options: whoever runs the script chooses them, as with $argv
    'getopt' => ['return'],
];
