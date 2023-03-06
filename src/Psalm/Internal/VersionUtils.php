<?php

namespace Psalm\Internal;

use Composer\InstalledVersions;
use OutOfBoundsException;
use Phar;

use function class_exists;
use function dirname;
use function file_put_contents;
use function var_export;

/**
 * @internal
 * @psalm-type _VersionData=array{"vimeo/psalm": string, "nikic/php-parser": string}
 */
final class VersionUtils
{
    private const PSALM_PACKAGE = 'vimeo/psalm';
    private const PHP_PARSER_PACKAGE = 'nikic/php-parser';

    /** @var null|_VersionData */
    private static ?array $versions = null;

    /** @psalm-suppress UnusedConstructor it's here to prevent instantiations */
    private function __construct()
    {
    }

    public static function getPsalmVersion(): string
    {
        return self::getVersions()[self::PSALM_PACKAGE];
    }

    public static function getPhpParserVersion(): string
    {
        return self::getVersions()[self::PHP_PARSER_PACKAGE];
    }

    /** @psalm-suppress UnusedMethod called from bin/build-phar.sh */
    public static function dump(): void
    {
        $versions = self::loadComposerVersions();
        $exported = '<?php return ' . var_export($versions, true) . ';';
        file_put_contents(dirname(__DIR__, 3) . '/build/phar-versions.php', $exported);
    }

    /** @return _VersionData */
    private static function getVersions(): array
    {
        if (self::$versions !== null) {
            return self::$versions;
        }

        if ($versions = self::loadPharVersions()) {
            return self::$versions = $versions;
        }

        if ($versions = self::loadComposerVersions()) {
            return self::$versions = $versions;
        }

        return self::$versions = [self::PSALM_PACKAGE => 'unknown', self::PHP_PARSER_PACKAGE => 'unknown'];
    }

    /** @return _VersionData|null */
    private static function loadPharVersions(): ?array
    {
        if (!class_exists(Phar::class)) {
            return null;
        }

        $phar_filename = Phar::running(true);

        if (!$phar_filename) {
            return null;
        }

        /**
         * @psalm-suppress UnresolvableInclude
         * @var _VersionData
         */
        return require($phar_filename . '/phar-versions.php');
    }

    /** @return _VersionData|null */
    private static function loadComposerVersions(): ?array
    {
        try {
            return [
                self::PSALM_PACKAGE => self::getVersion(self::PSALM_PACKAGE),
                self::PHP_PARSER_PACKAGE => self::getVersion(self::PHP_PARSER_PACKAGE),
            ];
        } catch (OutOfBoundsException $ex) {
        }
        return null;
    }

    private static function getVersion(string $packageName): string
    {
        return InstalledVersions::getPrettyVersion($packageName)
            . '@' . InstalledVersions::getReference($packageName);
    }
}
