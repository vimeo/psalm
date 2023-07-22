<?php

namespace Psalm\Tests\LanguageServer;

use PHPUnit\Framework\TestCase;
use Psalm\Internal\LanguageServer\PathMapper;

final class PathMapperTest extends TestCase
{
    public function testUsesUpdatedClientRoot(): void
    {
        $mapper = new PathMapper('/var/www');
        $mapper->configureClientRoot('/home/user/src/project');
        $this->assertSame(
            '/home/user/src/project/filename.php',
            $mapper->mapServerToClient('/var/www/filename.php')
        );
    }

    public function testIgnoresClientRootIfItWasPreconfigures(): void
    {
        $mapper = new PathMapper('/var/www', '/home/user/src/project');
        // this will be ignored
        $mapper->configureClientRoot('/home/anotheruser/Projects/project');

        $this->assertSame(
            '/home/user/src/project/filename.php',
            $mapper->mapServerToClient('/var/www/filename.php')
        );
    }

    /**
     * @dataProvider mappingProvider
     */
    public function testMapsClientToServer(
        string $serverRoot,
        ?string $clientRootPreconfigured,
        string $clientRootProvidedLater,
        string $clientPath,
        string $serverPath
    ): void {
        $mapper = new PathMapper($serverRoot, $clientRootPreconfigured);
        $mapper->configureClientRoot($clientRootProvidedLater);
        $this->assertSame(
            $serverPath,
            $mapper->mapClientToServer($clientPath)
        );
    }

    /** @dataProvider mappingProvider */
    public function testMapsServerToClient(
        string $serverRoot,
        ?string $clientRootPreconfigured,
        string $clientRootProvidedLater,
        string $clientPath,
        string $serverPath
    ): void {
        $mapper = new PathMapper($serverRoot, $clientRootPreconfigured);
        $mapper->configureClientRoot($clientRootProvidedLater);
        $this->assertSame(
            $clientPath,
            $mapper->mapServerToClient($serverPath)
        );
    }

    /** @return iterable<int, array{string, string|null, string, string, string}> */
    public static function mappingProvider(): iterable
    {
        yield ["/var/a",  null,             "/user/project", "/user/project/filename.php", "/var/a/filename.php"];
        yield ["/var/a",  "/user/project",  "/whatever",     "/user/project/filename.php", "/var/a/filename.php"];
        yield ["/var/a/", "/user/project",  "/whatever",     "/user/project/filename.php", "/var/a/filename.php"];
        yield ["/var/a",  "/user/project/", "/whatever",     "/user/project/filename.php", "/var/a/filename.php"];
        yield ["/var/a/", "/user/project/", "/whatever",     "/user/project/filename.php", "/var/a/filename.php"];
    }
}
