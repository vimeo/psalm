<?php

declare(strict_types=1);

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
            $mapper->mapServerToClient('/var/www/filename.php'),
        );
    }

    public function testIgnoresClientRootIfItWasPreconfigures(): void
    {
        $mapper = new PathMapper('/var/www', '/home/user/src/project');
        // this will be ignored
        $mapper->configureClientRoot('/home/anotheruser/Projects/project');

        $this->assertSame(
            '/home/user/src/project/filename.php',
            $mapper->mapServerToClient('/var/www/filename.php'),
        );
    }

    /**
     * @dataProvider mappingProvider
     */
    public function testMapsClientToServer(
        string $server_root,
        ?string $client_root_reconfigured,
        string $client_root_provided_later,
        string $client_path,
        string $server_ath,
    ): void {
        $mapper = new PathMapper($server_root, $client_root_reconfigured);
        $mapper->configureClientRoot($client_root_provided_later);
        $this->assertSame(
            $server_ath,
            $mapper->mapClientToServer($client_path),
        );
    }

    /** @dataProvider mappingProvider */
    public function testMapsServerToClient(
        string $server_root,
        ?string $client_root_preconfigured,
        string $client_root_provided_later,
        string $client_path,
        string $server_path,
    ): void {
        $mapper = new PathMapper($server_root, $client_root_preconfigured);
        $mapper->configureClientRoot($client_root_provided_later);
        $this->assertSame(
            $client_path,
            $mapper->mapServerToClient($server_path),
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
