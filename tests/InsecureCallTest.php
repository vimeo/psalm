<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_map;
use function sort;

final class InsecureCallTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'strongHashes' => [
                'code' => '<?php
                    function digest(string $data): string {
                        return hash("sha256", $data) . hash("SHA3-512", $data) . hash_hmac("md5", $data, "key");
                    }',
            ],
            'hashAlgorithmTheAnalysisCantTell' => [
                'code' => '<?php
                    function digest(string $algorithm, string $data): string {
                        return hash($algorithm, $data);
                    }',
            ],
            'authenticatedCipher' => [
                'code' => '<?php
                    function encrypt(string $data, string $key, string $iv): string {
                        return (string) openssl_encrypt($data, "aes-256-gcm", $key, 0, $iv, $tag);
                    }',
            ],
            'unseededRandomNumbers' => [
                'code' => '<?php
                    mt_srand();
                    echo mt_rand(), rand(), random_int(0, 10);',
            ],
            'mersenneTwisterSeededWithNull' => [
                'code' => '<?php
                    mt_srand(null);
                    echo (new Random\Randomizer(new Random\Engine\Mt19937(null)))->getInt(0, 10);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'unseededMersenneTwister' => [
                'code' => '<?php
                    echo (new Random\Randomizer(new Random\Engine\Mt19937()))->getInt(0, 10);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'secureCookies' => [
                'code' => '<?php
                    function remember(string $session): void {
                        setcookie("session", $session, 0, "/", "", true, true);
                        setcookie("session", $session, ["secure" => true, "httponly" => true]);
                        setrawcookie("session", $session, secure: true);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'cookieWhoseSecureFlagTheAnalysisCantTell' => [
                'code' => '<?php
                    function remember(string $session, bool $https, array $options): void {
                        setcookie("session", $session, 0, "/", "", $https);
                        setcookie("session", $session, $options);
                    }',
            ],
            'xmlParsedWithoutLoadingExternalEntities' => [
                'code' => '<?php
                    function parse(string $xml): void {
                        simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
                        (new DOMDocument())->loadXML($xml);
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
            'md5' => [
                'code' => '<?php
                    function digest(string $data): string {
                        return md5($data);
                    }',
                'error_message' => 'WeakHash',
            ],
            'sha1Algorithm' => [
                'code' => '<?php
                    function digest(string $data): string {
                        return hash("SHA1", $data);
                    }',
                'error_message' => 'WeakHash',
            ],
            'weakAlgorithmOneOfThePossibleOnes' => [
                'code' => '<?php
                    function digest(string $data, bool $legacy): string {
                        return hash($legacy ? "md5" : "sha256", $data);
                    }',
                'error_message' => 'WeakHash',
            ],
            'desCipher' => [
                'code' => '<?php
                    function encrypt(string $data, string $key): string {
                        return (string) openssl_encrypt($data, "des-ede3-cbc", $key, 0, "12345678");
                    }',
                'error_message' => 'WeakCipher',
            ],
            'ecbMode' => [
                'code' => '<?php
                    function decrypt(string $data, string $key): string {
                        return (string) openssl_decrypt($data, "AES-256-ECB", $key);
                    }',
                'error_message' => 'WeakCipher',
            ],
            'seededMtRand' => [
                'code' => '<?php
                    function token(int $seed): int {
                        mt_srand($seed);
                        return mt_rand();
                    }',
                'error_message' => 'WeakRandom',
            ],
            'mtRandSeededWithNullBeforePhp83' => [
                'code' => '<?php
                    mt_srand(null);
                    echo mt_rand();',
                'error_message' => 'WeakRandom',
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'seededMersenneTwister' => [
                'code' => '<?php
                    function token(int $seed): int {
                        return (new Random\Randomizer(new Random\Engine\Mt19937($seed)))->getInt(0, 10);
                    }',
                'error_message' => 'WeakRandom',
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'cookieWithoutSecureFlag' => [
                'code' => '<?php
                    function remember(string $session): void {
                        setcookie("session", $session);
                    }',
                'error_message' => 'InsecureCookie',
            ],
            'cookieWithTheSecureFlagOff' => [
                'code' => '<?php
                    function remember(string $session): void {
                        setcookie("session", $session, 0, "/", "", false, true);
                    }',
                'error_message' => 'InsecureCookie',
            ],
            'cookieOptionsWithoutSecureFlag' => [
                'code' => '<?php
                    function remember(string $session): void {
                        setcookie("session", $session, ["httponly" => true, "samesite" => "Strict"]);
                    }',
                'error_message' => 'InsecureCookie',
            ],
            'xmlParsedSubstitutingEntities' => [
                'code' => '<?php
                    function parse(string $xml): void {
                        simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NOENT);
                    }',
                'error_message' => 'XmlExternalEntity',
            ],
            'domDocumentLoadingTheDtd' => [
                'code' => '<?php
                    function parse(string $xml): void {
                        (new DOMDocument())->loadXML($xml, LIBXML_DTDLOAD | LIBXML_NONET);
                    }',
                'error_message' => 'XmlExternalEntity',
            ],
            'subclassOfDomDocument' => [
                'code' => '<?php
                    final class Document extends DOMDocument {}

                    function parse(string $xml): void {
                        (new Document())->loadXML($xml, LIBXML_NOENT);
                    }',
                'error_message' => 'XmlExternalEntity',
            ],
        ];
    }

    public function testAnInsecureCallIsReportedWhenItsArgumentsHaveTheWrongType(): void
    {
        $this->testConfig->throw_exception = false;

        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, '<?php
            function digest(array $data): string {
                return md5($data);
            }');

        $this->analyzeFile($file_path, new Context(), false);

        $types = array_map(
            static fn(IssueData $issue): string => $issue->type,
            IssueBuffer::getIssuesDataForFile($file_path),
        );
        sort($types);

        $this->assertSame(['InvalidArgument', 'WeakHash'], $types);
    }
}
