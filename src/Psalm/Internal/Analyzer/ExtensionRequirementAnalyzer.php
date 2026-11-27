<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Issue\MissingExtensionRequirement;
use Psalm\IssueBuffer;

use function str_replace;

/**
 * Reports the builtin functions and classes of extensions composer.json doesn't declare (see
 * Config::$declared_extensions).
 *
 * @internal
 */
final class ExtensionRequirementAnalyzer
{
    /**
     * The extensions PHP can't be built without (php-src has no configure option to disable them), from the PHP
     * version (as an id) they are always built in
     */
    private const CORE_EXTENSIONS = [
        'core' => 0,
        'date' => 0,
        'pcre' => 0,
        'reflection' => 0,
        'spl' => 0,
        'standard' => 0,
        'hash' => 7_04_00,
        'json' => 8_00_00,
        'random' => 8_02_00,
        'uri' => 8_05_00,
        'zend opcache' => 8_05_00,
    ];

    /**
     * The extensions that can't be loaded without another one (php-src's ZEND_MOD_REQUIRED), by the extension they
     * require, from the PHP version (as an id) they do
     */
    private const REQUIRING_EXTENSIONS = [
        'dom' => ['xsl' => 8_03_00],
        'libxml' => [
            'dom' => 0,
            'simplexml' => 0,
            'soap' => 8_04_00,
            'xmlreader' => 0,
            'xmlwriter' => 8_04_00,
            'xsl' => 0,
        ],
        'pdo' => [
            'pdo_dblib' => 0,
            'pdo_firebird' => 0,
            'pdo_mysql' => 0,
            'pdo_oci' => 0,
            'pdo_odbc' => 0,
            'pdo_pgsql' => 0,
            'pdo_sqlite' => 0,
        ],
    ];

    /**
     * A call of a builtin function, or a reference to a builtin class, interface or enum. A polyfill doesn't make the
     * extension optional: composer.json declares that with suggest, unless the polyfill package provides it.
     *
     * @param array<string> $suppressed_issues
     */
    public static function check(
        Codebase $codebase,
        string $function_or_class,
        bool $is_class,
        CodeLocation $code_location,
        ?Context $context,
        array $suppressed_issues,
    ): void {
        $composer_name = self::getUndeclaredExtension($codebase, $function_or_class, $is_class, $context);
        if ($composer_name === null) {
            return;
        }

        IssueBuffer::maybeAdd(
            new MissingExtensionRequirement(
                $function_or_class . ' is part of the ext-' . $composer_name . ' extension, which composer.json'
                    . ' doesn\'t require: add it to require, or to suggest if the code works without it',
                $code_location,
            ),
            $suppressed_issues,
        );
    }

    /**
     * The Composer name of an extension of the symbol in a PHP version the code runs on, unless it is part of the core
     * in that version, declared or checked
     */
    private static function getUndeclaredExtension(
        Codebase $codebase,
        string $function_or_class,
        bool $is_class,
        ?Context $context,
    ): ?string {
        $declared_extensions = $codebase->config->declared_extensions;
        if ($declared_extensions === null) {
            return null;
        }

        // the code also runs on the older PHP versions composer.json allows, which may have been built without it
        foreach (InternalCallMapHandler::getExtensions($function_or_class, $is_class) as $extension => $since) {
            $core_since = self::CORE_EXTENSIONS[$extension] ?? null;
            if ($core_since !== null && $core_since <= $since) {
                continue;
            }

            if (self::isDeclaredOrChecked($extension, $declared_extensions, $context)) {
                continue;
            }

            // an extension requiring it can't be loaded without it
            foreach (self::REQUIRING_EXTENSIONS[$extension] ?? [] as $requiring_extension => $requiring_since) {
                if ($requiring_since <= $since
                    && self::isDeclaredOrChecked($requiring_extension, $declared_extensions, $context)
                ) {
                    continue 2;
                }
            }

            return str_replace(' ', '-', $extension);
        }

        return null;
    }

    /**
     * @param array<string, true> $declared_extensions
     * @psalm-capabilities read-props
     */
    private static function isDeclaredOrChecked(
        string $extension,
        array $declared_extensions,
        ?Context $context,
    ): bool {
        return isset($declared_extensions[str_replace(' ', '-', $extension)])
            || ($extension === 'zend opcache' && isset($declared_extensions['opcache']))
            || ($context?->vars_in_scope[Context::getExtensionVarId($extension)] ?? null)?->isTrue() === true;
    }
}
