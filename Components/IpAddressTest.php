<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\Uri\Components;

use League\Uri\Contracts\UriComponentInterface;
use League\Uri\Exceptions\SyntaxError;
use PHPUnit\Framework\TestCase;
use Stringable;

/**
 * @group host
 * @coversDefaultClass \League\Uri\Components\Host
 */
final class IpAddressTest extends TestCase
{
    /**
     * @dataProvider validIpAddressProvider
     */
    public function testValidIpAddress(
        UriComponentInterface|Stringable|int|string|null $host,
        bool $isDomain,
        bool $isIp,
        bool $isIpv4,
        bool $isIpv6,
        bool $isIpFuture,
        ?string $ipVersion,
        string $uri,
        ?string $ip,
        string $iri
    ): void {
        $host = null === $host ? Host::new() : Host::createFromString((string) $host);

        self::assertSame($isIp, $host->isIp());
        self::assertSame($isIpv4, $host->isIpv4());
        self::assertSame($isIpv6, $host->isIpv6());
        self::assertSame($isIpFuture, $host->isIpFuture());
        self::assertSame($ip, $host->getIp());
        self::assertSame($ipVersion, $host->getIpVersion());
    }

    public static function validIpAddressProvider(): array
    {
        return [
            'ip host object' => [
                Host::createFromIp('127.0.0.1'),
                false,
                true,
                true,
                false,
                false,
                '4',
                '127.0.0.1',
                '127.0.0.1',
                '127.0.0.1',
            ],
            'ipv4' => [
                '127.0.0.1',
                false,
                true,
                true,
                false,
                false,
                '4',
                '127.0.0.1',
                '127.0.0.1',
                '127.0.0.1',
            ],
            'ipv6' => [
                '[::1]',
                false,
                true,
                false,
                true,
                false,
                '6',
                '[::1]',
                '::1',
                '[::1]',
            ],
            'scoped ipv6' => [
                '[fe80:1234::%251]',
                false,
                true,
                false,
                true,
                false,
                '6',
                '[fe80:1234::%251]',
                'fe80:1234::%1',
                '[fe80:1234::%251]',
            ],
            'ipfuture' => [
                '[v1.ZZ.ZZ]',
                false,
                true,
                false,
                false,
                true,
                '1',
                '[v1.ZZ.ZZ]',
                'ZZ.ZZ',
                '[v1.ZZ.ZZ]',
            ],
            'registered name' => [
                'uri.thephpleague.com',
                false,
                false,
                false,
                false,
                false,
                null,
                'uri.thephpleague.com',
                null,
                'uri.thephpleague.com',
            ],
        ];
    }

    /**
     * @dataProvider createFromIpValid
     */
    public function testCreateFromIp(string $input, string $version, string $expected): void
    {
        self::assertSame($expected, (string) Host::createFromIp($input, $version));
    }

    public static function createFromIpValid(): array
    {
        return [
            'ipv4' => ['127.0.0.1', '', '127.0.0.1'],
            'ipv4 does care about the version string' => ['127.0.0.1', 'FA', '[vFA.127.0.0.1]'],
            'ipv6' => ['::1', '', '[::1]'],
            'ipv6 does care about the version string' => ['::1', '12', '[v12.::1]'],
            'ipv6 with scope' => ['fe80:1234::%1', '', '[fe80:1234::%251]'],
            'valid IpFuture' => ['csucj.$&+;::', 'AF', '[vAF.csucj.$&+;::]'],
            'octal IP' => ['0300.0250.0000.0001', '', '192.168.0.1'],
        ];
    }

    /**
     * @dataProvider createFromIpFailed
     */
    public function testCreateFromIpFailed(string $input): void
    {
        $this->expectException(SyntaxError::class);
        Host::createFromIp($input);
    }

    public static function createFromIpFailed(): array
    {
        return [
            'false ipv4' => ['127..1'],
            'hostname' => ['example.com'],
            'false ipfuture' => ['vAF.csucj.$&+;:/:'],
            'formatted ipv6' => ['[::1]'],
        ];
    }

    /**
     * @dataProvider withoutZoneIdentifierProvider
     */
    public function testWithoutZoneIdentifier(string $host, string $expected): void
    {
        self::assertSame($expected, (string) Host::createFromString($host)->withoutZoneIdentifier());
    }

    public static function withoutZoneIdentifierProvider(): array
    {
        return [
            'ipv4 host' => ['127.0.0.1', '127.0.0.1'],
            'ipv6 host' => ['[::1]', '[::1]'],
            'ipv6 scoped (1)' => ['[fe80::%251]', '[fe80::]'],
            'ipv6 scoped (2)' => ['[fe80::%1]', '[fe80::]'],
        ];
    }

    /**
     * @dataProvider hasZoneIdentifierProvider
     */
    public function testHasZoneIdentifier(string $host, bool $expected): void
    {
        self::assertSame($expected, Host::createFromString($host)->hasZoneIdentifier());
    }

    public static function hasZoneIdentifierProvider(): array
    {
        return [
            ['127.0.0.1', false],
            ['[::1]', false],
            ['[fe80::%251]', true],
        ];
    }
}
