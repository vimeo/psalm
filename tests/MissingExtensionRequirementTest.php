<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function getcwd;

use const DIRECTORY_SEPARATOR;

final class MissingExtensionRequirementTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    #[Override]
    protected function makeConfig(): Config
    {
        $config = parent::makeConfig();
        $config->declared_extensions = ['pdo' => true, 'sodium' => true];

        return $config;
    }

    public function testNothingIsReportedWithoutAComposerJson(): void
    {
        $this->project_analyzer->getConfig()->declared_extensions = null;

        $file_path = (string) getcwd() . '/src/somefile.php';
        $this->addFile($file_path, '<?php
            function length(string $s): int {
                return mb_strlen($s);
            }');

        $this->analyzeFile($file_path, new Context());
    }

    public function testCoreExtensionOfAPhpNewerThanComposerAllows(): void
    {
        $this->project_analyzer->getConfig()->lowest_composer_php_version_id = 7_03_00;
        $this->project_analyzer->setPhpVersion('8.2', 'tests');

        $file_path = (string) getcwd() . '/src/somefile.php';
        $this->addFile($file_path, '<?php
            function digest(string $s): string {
                return hash("sha256", $s);
            }');

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('hash is part of the ext-hash extension');

        $this->analyzeFile($file_path, new Context());
    }

    public function testCoreExtensionsOfTheLowestPhpComposerAllows(): void
    {
        $this->project_analyzer->getConfig()->lowest_composer_php_version_id = 8_02_00;
        $this->project_analyzer->setPhpVersion('8.4', 'tests');

        $file_path = (string) getcwd() . '/src/somefile.php';
        $this->addFile($file_path, '<?php
            function f(string $s): string {
                return hash("sha256", $s) . json_encode($s) . random_int(1, 2);
            }');

        $this->analyzeFile($file_path, new Context());
    }

    public function testFunctionMovedIntoACoreExtensionByANewerPhpThanComposerAllows(): void
    {
        $this->project_analyzer->getConfig()->lowest_composer_php_version_id = 8_01_00;
        $this->project_analyzer->setPhpVersion('8.3', 'tests');

        $file_path = (string) getcwd() . '/src/somefile.php';
        $this->addFile($file_path, '<?php
            function f(): int {
                return random_int(1, 2) + mt_rand();
            }');

        $this->analyzeFile($file_path, new Context());
    }

    public function testFunctionOfAnExtensionInAnOlderPhpComposerAllows(): void
    {
        // utf8_encode() moved from ext-xml to the core in PHP 7.2, and is deprecated since 8.2
        $this->project_analyzer->getConfig()->setCustomErrorLevel('DeprecatedFunction', Config::REPORT_SUPPRESS);
        $this->project_analyzer->getConfig()->lowest_composer_php_version_id = 7_01_00;
        $this->project_analyzer->setPhpVersion('7.4', 'tests');

        $file_path = (string) getcwd() . '/src/somefile.php';
        $this->addFile($file_path, '<?php
            function f(string $s): string {
                return utf8_encode($s);
            }');

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('utf8_encode is part of the ext-xml extension');

        $this->analyzeFile($file_path, new Context());
    }

    public function testCheckOfAFunctionOfAnExtensionInAnOlderPhpComposerAllows(): void
    {
        // utf8_encode() moved from ext-xml to the core in PHP 7.2, and is deprecated since 8.2
        $this->project_analyzer->getConfig()->setCustomErrorLevel('DeprecatedFunction', Config::REPORT_SUPPRESS);
        $this->project_analyzer->getConfig()->lowest_composer_php_version_id = 7_01_00;
        $this->project_analyzer->setPhpVersion('7.4', 'tests');

        $file_path = (string) getcwd() . '/src/somefile.php';
        $this->addFile($file_path, '<?php
            function f(string $s): string {
                return function_exists("utf8_encode") ? utf8_encode($s) : $s;
            }');

        $this->analyzeFile($file_path, new Context());
    }

    public function testFunctionAddedAfterTheLowestPhpComposerAllows(): void
    {
        $this->project_analyzer->getConfig()->lowest_composer_php_version_id = 7_04_00;
        $this->project_analyzer->setPhpVersion('8.3', 'tests');

        $file_path = (string) getcwd() . '/src/somefile.php';
        $this->addFile($file_path, '<?php
            function f(string $s): bool {
                return json_validate($s);
            }');

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'coreExtensions' => [
                'code' => '<?php
                    function f(string $s): string {
                        $d = new DateTime();
                        $o = new ArrayObject([]);
                        return strlen($s) . preg_quote($s) . hash("sha256", $s) . json_encode($s)
                            . $d->format("Y") . count($o) . random_int(1, 2);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'extensionsAlwaysBuiltFromPhp85' => [
                'code' => '<?php
                    function f(string $url): bool {
                        return Uri\Rfc3986\Uri::parse($url) !== null && opcache_get_status() !== false;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'functionOfACoreExtensionBeforeItMovedToAnother' => [
                'code' => '<?php
                    function f(): int {
                        return random_int(1, 2) + mt_rand();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'declaredExtensions' => [
                'code' => '<?php
                    function f(string $s): string {
                        $pdo = new PDO("sqlite::memory:");
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        return sodium_bin2hex($s);
                    }',
            ],
            'referencesThatDontNeedTheClass' => [
                'code' => '<?php
                    /** @param SoapClient $docblock */
                    function f(Redis $r, mixed $x, $docblock): ?Redis {
                        $name = Redis::class;
                        return $x instanceof Redis ? $x : null;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'extensionLoaded' => [
                'code' => '<?php
                    function f(string $s): int {
                        if (extension_loaded("mbstring")) {
                            return mb_strlen($s);
                        }
                        return strlen($s);
                    }',
            ],
            'extensionLoadedEarlyReturn' => [
                'code' => '<?php
                    function f(string $s): int {
                        if (!extension_loaded("mbstring")) {
                            return strlen($s);
                        }
                        return mb_strlen($s);
                    }',
            ],
            'extensionLoadedInTheCondition' => [
                'code' => '<?php
                    function f(string $s): bool {
                        return extension_loaded("ctype") && ctype_digit($s);
                    }',
            ],
            'functionOfTheExtensionExists' => [
                'code' => '<?php
                    function f(string $s): string {
                        if (function_exists("iconv")) {
                            return (string) iconv("UTF-8", "ASCII", $s) . iconv_strlen($s);
                        }
                        return $s;
                    }',
            ],
            'functionOfTheExtensionExistsEarlyReturn' => [
                'code' => '<?php
                    function f(string $s): int {
                        if (!function_exists("iconv_strlen")) {
                            return strlen($s);
                        }
                        return (int) iconv_strlen($s);
                    }',
            ],
            'classOfTheExtensionExists' => [
                'code' => '<?php
                    function f(): ?ZipArchive {
                        return class_exists(ZipArchive::class) ? new ZipArchive() : null;
                    }',
            ],
            'closureOfAGuardedBranch' => [
                'code' => '<?php
                    function f(): ?Closure {
                        if (extension_loaded("posix")) {
                            return fn(): int => posix_getpid();
                        }
                        return null;
                    }',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'functionCall' => [
                'code' => '<?php
                    function f(string $s): int {
                        return mb_strlen($s);
                    }',
                'error_message' => 'MissingExtensionRequirement - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32'
                    . ' - mb_strlen is part of the ext-mbstring extension',
            ],
            'new' => [
                'code' => '<?php
                    function f(): ZipArchive {
                        return new ZipArchive();
                    }',
                'error_message' => 'MissingExtensionRequirement - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:36',
            ],
            'staticCall' => [
                'code' => '<?php
                    function f(): ?int {
                        return IntlChar::ord("a");
                    }',
                'error_message' => 'IntlChar is part of the ext-intl extension',
            ],
            'classConstant' => [
                'code' => '<?php
                    function f(): int {
                        return ZipArchive::CREATE;
                    }',
                'error_message' => 'ZipArchive is part of the ext-zip extension',
            ],
            'extends' => [
                'code' => '<?php
                    final class Client extends SoapClient {}',
                'error_message' => 'SoapClient is part of the ext-soap extension',
            ],
            'implementsAnInterfaceOfJsonBeforePhp8' => [
                'code' => '<?php
                    final class A implements JsonSerializable {
                        public function jsonSerialize(): array {
                            return [];
                        }
                    }',
                'error_message' => 'JsonSerializable is part of the ext-json extension',
            ],
            'guardOfAnotherExtension' => [
                'code' => '<?php
                    function f(string $s): int {
                        if (extension_loaded("intl")) {
                            return mb_strlen($s);
                        }
                        return 0;
                    }',
                'error_message' => 'mb_strlen is part of the ext-mbstring extension',
            ],
            'branchWhereTheExtensionIsMissing' => [
                'code' => '<?php
                    function f(string $s): int {
                        if (!extension_loaded("mbstring")) {
                            return mb_strlen($s);
                        }
                        return 0;
                    }',
                'error_message' => 'mb_strlen is part of the ext-mbstring extension',
            ],
            'branchWhereAFunctionOfTheExtensionIsMissing' => [
                'code' => '<?php
                    function f(string $s): int {
                        if (!function_exists("iconv")) {
                            return (int) iconv_strlen($s);
                        }
                        return 0;
                    }',
                'error_message' => 'iconv_strlen is part of the ext-iconv extension',
            ],
            'functionOfTheExtensionIdenticalToFalseSaysNothingOfAnotherOne' => [
                'code' => '<?php
                    function f(): void {
                        if (function_exists("iconv") && function_exists("iconv_strlen") === false) {
                            echo (string) iconv("a", "b", "c");
                        }
                        echo (int) iconv_strlen("a");
                    }',
                'error_message' => 'MissingExtensionRequirement - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:36',
            ],
            'functionOfTheExtensionNotIdenticalToTrueSaysNothingOfAnotherOne' => [
                'code' => '<?php
                    function f(): void {
                        if (!function_exists("iconv")) {
                            return;
                        }
                        if (function_exists("iconv_strlen") !== true) {
                            echo (string) iconv("a", "b", "c");
                        }
                        echo mb_strlen("a");
                    }',
                'error_message' => 'mb_strlen is part of the ext-mbstring extension',
            ],
            'afterTheGuardedBranch' => [
                'code' => '<?php
                    function f(string $s): int {
                        if (extension_loaded("mbstring")) {
                            echo mb_strlen($s);
                        }
                        return mb_strlen($s);
                    }',
                'error_message' => 'MissingExtensionRequirement - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:32',
            ],
            'extensionTheStubImageOfTheVersionDoesntBuild' => [
                'code' => '<?php
                    function f(): Redis {
                        return new Redis();
                    }',
                'error_message' => 'Redis is part of the ext-redis extension',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'bundledExtension' => [
                'code' => '<?php
                    function f(): bool {
                        return socket_create(2, 1, 6) !== false;
                    }',
                'error_message' => 'socket_create is part of the ext-sockets extension',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'composerNameOfOpcache' => [
                'code' => '<?php
                    function f(): bool {
                        return opcache_get_status() !== false;
                    }',
                'error_message' => 'opcache_get_status is part of the ext-zend-opcache extension',
            ],
            'polyfillDeclaredInTheCode' => [
                'code' => '<?php
                    if (!function_exists("bcadd")) {
                        function bcadd(string $a, string $b): string {
                            return (string) ((int) $a + (int) $b);
                        }
                    }
                    echo bcadd("1", "2");',
                'error_message' => 'bcadd is part of the ext-bcmath extension',
            ],
        ];
    }
}
