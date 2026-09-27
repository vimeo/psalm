<?php

use Psalm\StrId;
use Psalm\Type\TaintKind;

// This maps internal function and method names (canonical, case-sensitive) to sink types that we don’t want to
// end up there, by parameter offset

/**
 * @var array{
 *     functions: array<int, non-empty-list<int-mask-of<TaintKind::*>>>,
 *     methods: array<int, array<int, non-empty-list<int-mask-of<TaintKind::*>>>>
 * }
 */
return [
    'functions' => [
        StrId::exec => [TaintKind::INPUT_SHELL],
        StrId::create_function => [0, TaintKind::INPUT_EVAL],
        StrId::file_get_contents => [TaintKind::INPUT_FILE|TaintKind::INPUT_SSRF],
        StrId::file_put_contents => [TaintKind::INPUT_FILE],
        StrId::fopen => [TaintKind::INPUT_FILE],
        StrId::unlink => [TaintKind::INPUT_FILE],
        StrId::copy => [TaintKind::INPUT_FILE|TaintKind::INPUT_SSRF, TaintKind::INPUT_FILE],
        StrId::file => [TaintKind::INPUT_FILE],
        StrId::link => [TaintKind::INPUT_FILE, TaintKind::INPUT_FILE],
        StrId::mkdir => [TaintKind::INPUT_FILE],
        StrId::move_uploaded_file => [TaintKind::INPUT_FILE, TaintKind::INPUT_FILE],
        StrId::parse_ini_file => [TaintKind::INPUT_FILE],
        StrId::chown => [TaintKind::INPUT_FILE],
        StrId::lchown => [TaintKind::INPUT_FILE],
        StrId::readfile => [TaintKind::INPUT_FILE],
        StrId::rename => [TaintKind::INPUT_FILE, TaintKind::INPUT_FILE],
        StrId::rmdir => [TaintKind::INPUT_FILE],
        StrId::header => [TaintKind::INPUT_HEADER],
        StrId::symlink => [TaintKind::INPUT_FILE],
        StrId::tempnam => [TaintKind::INPUT_FILE],
        StrId::igbinary_unserialize => [TaintKind::INPUT_UNSERIALIZE],
        StrId::ldap_search => [0, TaintKind::INPUT_LDAP, TaintKind::INPUT_LDAP],
        StrId::mysqli_query => [0, TaintKind::INPUT_SQL],
        StrId::mysqli_real_query => [0, TaintKind::INPUT_SQL],
        StrId::mysqli_multi_query => [0, TaintKind::INPUT_SQL],
        StrId::mysqli_prepare => [0, TaintKind::INPUT_SQL],
        StrId::mysqli_stmt_prepare => [0, TaintKind::INPUT_SQL],
        StrId::passthru => [TaintKind::INPUT_SHELL],
        StrId::pcntl_exec => [TaintKind::INPUT_SHELL],
        StrId::pg_exec => [0, TaintKind::INPUT_SQL],
        StrId::pg_prepare => [0, 0, TaintKind::INPUT_SQL],
        StrId::pg_put_line => [0, TaintKind::INPUT_SQL],
        StrId::pg_query => [0, TaintKind::INPUT_SQL],
        StrId::pg_query_params => [0, TaintKind::INPUT_SQL],
        StrId::pg_send_prepare => [0, 0, TaintKind::INPUT_SQL],
        StrId::pg_send_query => [0, TaintKind::INPUT_SQL],
        StrId::pg_send_query_params => [0, TaintKind::INPUT_SQL, 0],
        StrId::setcookie => [TaintKind::INPUT_COOKIE, TaintKind::INPUT_COOKIE],
        StrId::shell_exec => [TaintKind::INPUT_SHELL],
        StrId::system => [TaintKind::INPUT_SHELL],
        StrId::unserialize => [TaintKind::INPUT_UNSERIALIZE],
        StrId::popen => [TaintKind::INPUT_SHELL],
        StrId::proc_open => [TaintKind::INPUT_SHELL],
        StrId::curl_init => [TaintKind::INPUT_SSRF],
        StrId::curl_setopt => [0, 0, TaintKind::INPUT_SSRF],
        StrId::getimagesize => [TaintKind::INPUT_SSRF],
    ],
    'methods' => [
        StrId::mysqli => [
            StrId::query => [TaintKind::INPUT_SQL],
            StrId::real_query => [TaintKind::INPUT_SQL],
            StrId::multi_query => [TaintKind::INPUT_SQL],
            StrId::prepare => [TaintKind::INPUT_SQL],
        ],
        StrId::mysqli_stmt => [
            StrId::__construct => [0, TaintKind::INPUT_SQL],
            StrId::prepare => [TaintKind::INPUT_SQL],
        ],
    ],
];
