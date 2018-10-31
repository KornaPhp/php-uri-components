<?php

/**
 * League.Uri (http://uri.thephpleague.com/components).
 *
 * @package    League\Uri
 * @subpackage League\Uri\Components
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-components/blob/master/LICENSE (MIT License)
 * @version    2.0.0
 * @link       https://github.com/thephpleague/uri-schemes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LeagueTest\Uri\Component;

use League\Uri\Component\Path;
use League\Uri\Exception\InvalidUriComponent;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * @group path
 * @group defaultpath
 * @coversDefaultClass \League\Uri\Component\Path
 */
class PathTest extends TestCase
{
    /**
     * @dataProvider validPathEncoding
     *
     * @covers ::__construct
     * @covers ::validate
     * @covers ::decodeMatches
     * @covers ::decoded
     * @covers ::getContent
     * @covers ::encodeComponent
     */
    public function testGetUriComponent($decoded, $encoded)
    {
        $path = new Path($decoded);
        self::assertSame($decoded, $path->decoded());
        self::assertSame($encoded, $path->getContent());
    }

    public function validPathEncoding()
    {
        return [
            [
                'toto',
                'toto',
            ],
            [
                'bar---',
                'bar---',
            ],
            [
                '',
                '',
                '',
            ],
            [
                '"bad"',
                '%22bad%22',
            ],
            [
                '<not good>',
                '%3Cnot%20good%3E',
            ],
            [
                '{broken}',
                '%7Bbroken%7D',
            ],
            [
                '`oops`',
                '%60oops%60',
            ],
            [
                '\\slashy',
                '%5Cslashy',
            ],
            [
                'foo^bar',
                'foo%5Ebar',
            ],
            [
                'foo^bar/baz',
                'foo%5Ebar/baz',
            ],
            [
                'foo%2Fbar',
                'foo%2Fbar',
            ],
            [
                '/v1/people/%7E:(first-name,last-name,email-address,picture-url)',
                '/v1/people/%7E:(first-name,last-name,email-address,picture-url)',
            ],
            [
                '/v1/people/~:(first-name,last-name,email-address,picture-url)',
                '/v1/people/~:(first-name,last-name,email-address,picture-url)',
            ],
        ];
    }

    public function testWithContent()
    {
        $component = new Path('this is a normal path');
        self::assertSame($component, $component->withContent($component));
        self::assertNotSame($component, $component->withContent('new/path'));
    }

    /**
     * @dataProvider invalidPath
     *
     */
    public function testConstructorThrowsWithInvalidData($path)
    {
        self::expectException(TypeError::class);
        new Path($path);
    }

    public function invalidPath()
    {
        return [
            [date_create()],
            [[]],
            [null],
        ];
    }

    public function testConstructorThrowsExceptionWithInvalidData()
    {
        self::expectException(InvalidUriComponent::class);
        new Path("\0");
    }

    public function testSetState()
    {
        $component = new Path(42);
        $generateComponent = eval('return '.var_export($component, true).';');
        self::assertEquals($component, $generateComponent);
    }

    /**
     * Test Removing Dot Segment.
     *
     * @param string $expected
     * @param string $path
     * @dataProvider normalizeProvider
     */
    public function testWithoutDotSegments($path, $expected)
    {
        self::assertSame($expected, (new Path($path))->withoutDotSegments()->__toString());
    }

    /**
     * Provides different segment to be normalized.
     *
     * @return array
     */
    public function normalizeProvider()
    {
        return [
            ['/a/b/c/./../../g', '/a/g'],
            ['mid/content=5/../6', 'mid/6'],
            ['a/b/c', 'a/b/c'],
            ['a/b/c/.', 'a/b/c/'],
            ['/a/b/c', '/a/b/c'],
        ];
    }

    /**
     * @param string $path
     * @param string $expected
     * @dataProvider withoutEmptySegmentsProvider
     */
    public function testWithoutEmptySegments($path, $expected)
    {
        self::assertSame($expected, (string) (new Path($path))->withoutEmptySegments());
    }

    public function withoutEmptySegmentsProvider()
    {
        return [
            ['/a/b/c', '/a/b/c'],
            ['//a//b//c', '/a/b/c'],
            ['a//b/c//', 'a/b/c/'],
            ['/a/b/c//', '/a/b/c/'],
        ];
    }

    /**
     * @param string $path
     * @param bool   $expected
     * @dataProvider trailingSlashProvider
     */
    public function testHasTrailingSlash($path, $expected)
    {
        self::assertSame($expected, (new Path($path))->hasTrailingSlash());
    }

    public function trailingSlashProvider()
    {
        return [
            ['/path/to/my/', true],
            ['/path/to/my', false],
            ['path/to/my', false],
            ['path/to/my/', true],
            ['/', true],
            ['', false],
        ];
    }

    /**
     * @param string $path
     * @param string $expected
     * @dataProvider withTrailingSlashProvider
     */
    public function testWithTrailingSlash($path, $expected)
    {
        self::assertSame($expected, (string) (new Path($path))->withTrailingSlash());
    }

    public function withTrailingSlashProvider()
    {
        return [
            'relative path without ending slash' => ['toto', 'toto/'],
            'absolute path without ending slash' => ['/toto', '/toto/'],
            'root path' => ['/', '/'],
            'empty path' => ['', '/'],
            'relative path with ending slash' => ['toto/', 'toto/'],
            'absolute path with ending slash' => ['/toto/', '/toto/'],
        ];
    }

    /**
     * @param string $path
     * @param string $expected
     * @dataProvider withoutTrailingSlashProvider
     */
    public function testWithoutTrailingSlash($path, $expected)
    {
        self::assertSame($expected, (string) (new Path($path))->withoutTrailingSlash());
    }

    public function withoutTrailingSlashProvider()
    {
        return [
            'relative path without ending slash' => ['toto', 'toto'],
            'absolute path without ending slash' => ['/toto', '/toto'],
            'root path' => ['/', ''],
            'empty path' => ['', ''],
            'relative path with ending slash' => ['toto/', 'toto'],
            'absolute path with ending slash' => ['/toto/', '/toto'],
        ];
    }

    /**
     * @param string $path
     * @param string $expected
     * @dataProvider withLeadingSlashProvider
     */
    public function testWithLeadingSlash($path, $expected)
    {
        self::assertSame($expected, (string) (new Path($path))->withLeadingSlash());
    }

    public function withLeadingSlashProvider()
    {
        return [
            'relative path without leading slash' => ['toto', '/toto'],
            'absolute path' => ['/toto', '/toto'],
            'root path' => ['/', '/'],
            'empty path' => ['', '/'],
            'relative path with ending slash' => ['toto/', '/toto/'],
            'absolute path with ending slash' => ['/toto/', '/toto/'],
        ];
    }

    /**
     * @param string $path
     * @param string $expected
     * @dataProvider withoutLeadingSlashProvider
     */
    public function testWithoutLeadingSlash($path, $expected)
    {
        self::assertSame($expected, (string) (new Path($path))->withoutLeadingSlash());
    }

    public function withoutLeadingSlashProvider()
    {
        return [
            'relative path without ending slash' => ['toto', 'toto'],
            'absolute path without ending slash' => ['/toto', 'toto'],
            'root path' => ['/', ''],
            'empty path' => ['', ''],
            'absolute path with ending slash' => ['/toto/', 'toto/'],
        ];
    }
}