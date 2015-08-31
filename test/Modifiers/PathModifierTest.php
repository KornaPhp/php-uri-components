<?php

namespace League\Uri\Test\Modifiers;

use League\Uri\Components\FtpPath;
use League\Uri\Components\HierarchicalPath;
use League\Uri\Modifiers\AddLeadingSlash;
use League\Uri\Modifiers\AddTrailingSlash;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Modifiers\DataUriParameters;
use League\Uri\Modifiers\DataUriToAscii;
use League\Uri\Modifiers\DataUriToBinary;
use League\Uri\Modifiers\Extension;
use League\Uri\Modifiers\FilterSegments;
use League\Uri\Modifiers\PrependSegment;
use League\Uri\Modifiers\RemoveDotSegments;
use League\Uri\Modifiers\RemoveEmptySegments;
use League\Uri\Modifiers\RemoveLeadingSlash;
use League\Uri\Modifiers\RemoveSegments;
use League\Uri\Modifiers\RemoveTrailingSlash;
use League\Uri\Modifiers\ReplaceSegment;
use League\Uri\Modifiers\Typecode;
use League\Uri\Schemes\Data as DataUri;
use League\Uri\Schemes\Http as HttpUri;
use PHPUnit_Framework_TestCase;

/**
 * @group path
 * @group modifier
 */
class PathModifierTest extends PHPUnit_Framework_TestCase
{
    private $uri;

    public function setUp()
    {
        $this->uri = HttpUri::createFromString(
            'http://www.example.com/path/to/the/sky.php?kingkong=toto&foo=bar+baz#doc3'
        );
    }

    /**
     * @dataProvider fileProvider
     * @param $uri
     */
    public function testToBinary($binary, $ascii)
    {
        $modifier = new DataUriToBinary();
        $this->assertSame((string) $binary, (string) $modifier($ascii));
    }

    /**
     * @dataProvider fileProvider
     * @param $uri
     */
    public function testToAscii($binary, $ascii)
    {
        $modifier = new DataUriToAscii();
        $this->assertSame((string) $ascii, (string) $modifier($binary));
    }

    public function fileProvider()
    {
        $ascii = DataUri::createFromString('data:text/plain;charset=us-ascii,Bonjour%20le%20monde%21');
        $binary = DataUri::createFromString('data:'.$ascii->path->toBinary());

        $pathBin = DataUri::createFromPath(dirname(__DIR__).'/data/red-nose.gif');
        $pathAscii = DataUri::createFromString('data:'.$pathBin->path->toAscii());

        return [
            [$pathBin, $pathAscii],
            [$binary, $ascii],
        ];
    }

    public function testDataUriWithParameters()
    {
        $modifier = (new DataUriParameters(''))->withParameters('coco=chanel');
        $uri = DataUri::createFromString('data:text/plain;charset=us-ascii,Bonjour%20le%20monde!');
        $this->assertSame('text/plain;coco=chanel,Bonjour%20le%20monde!', (string) $modifier($uri)->getPath());
    }

    /**
     * @dataProvider validPathProvider
     */
    public function testAppendProcess($segment, $key, $append, $prepend, $replace)
    {
        $modifier = (new AppendSegment($segment))->withSegment($segment);
        $this->assertSame(
            $append,
            $modifier($this->uri)->getPath()
        );
    }

    /**
     * @dataProvider validPathProvider
     */
    public function testPrependProcess($segment, $key, $append, $prepend, $replace)
    {
        $modifier = (new PrependSegment($segment))->withSegment($segment);
        $this->assertSame(
            $prepend,
            $modifier($this->uri)->getPath()
        );
    }

    /**
     * @dataProvider validPathProvider
     */
    public function testReplaceSegmentProcess($segment, $key, $append, $prepend, $replace)
    {
        $modifier = (new ReplaceSegment($key, $segment))->withSegment($segment)->withOffset($key);

        $this->assertSame(
            $replace,
            $modifier($this->uri)->getPath()
        );
    }

    public function validPathProvider()
    {
        return [
            ['toto', 2, '/path/to/the/sky.php/toto', '/toto/path/to/the/sky.php', '/path/to/toto/sky.php'],
            ['le blanc', 2, '/path/to/the/sky.php/le%20blanc', '/le%20blanc/path/to/the/sky.php', '/path/to/le%20blanc/sky.php'],
            [new HierarchicalPath('toto'), 2, '/path/to/the/sky.php/toto', '/toto/path/to/the/sky.php', '/path/to/toto/sky.php'],
        ];
    }

    /**
     * @dataProvider validWithoutSegmentsProvider
     */
    public function testWithoutSegments($keys, $expected)
    {
        $modifier = (new RemoveSegments($keys))->withKeys($keys);

        $this->assertSame($expected, $modifier($this->uri)->getPath());
    }

    public function validWithoutSegmentsProvider()
    {
        return [
            [[1], '/path/the/sky.php'],
        ];
    }

    public function testFilterSegments()
    {
        $modifier = new FilterSegments(function ($value) {
            return $value > 0 && $value < 2;
        }, HierarchicalPath::FILTER_USE_KEY);

        $this->assertSame('/to', $modifier($this->uri)->getPath());
    }

    public function testWithoutDotSegmentsProcess()
    {
        $uri = HttpUri::createFromString(
            'http://www.example.com/path/../to/the/./sky.php?kingkong=toto&foo=bar+baz#doc3'
        );
        $modifier = new RemoveDotSegments();
        $this->assertSame('/to/the/sky.php', $modifier($uri)->getPath());
    }

    public function testWithoutEmptySegmentsProcess()
    {
        $uri = HttpUri::createFromString(
            'http://www.example.com/path///to/the//sky.php?kingkong=toto&foo=bar+baz#doc3'
        );
        $modifier = new RemoveEmptySegments();
        $this->assertSame('/path/to/the/sky.php', $modifier($uri)->getPath());
    }

    public function testWithoutTrailingSlashProcess()
    {
        $uri = HttpUri::createFromString('http://www.example.com/');
        $modifier = new RemoveTrailingSlash();
        $this->assertSame('', $modifier($uri)->getPath());
    }

    /**
     * @dataProvider validExtensionProvider
     */
    public function testExtensionProcess($extension, $expected)
    {
        $modifier = (new Extension(''))->withExtension($extension);

        $this->assertSame($expected, $modifier($this->uri)->getPath());
    }

    public function validExtensionProvider()
    {
        return [
            ['csv', '/path/to/the/sky.csv'],
            ['.csv', '/path/to/the/sky.csv'],
            ['', '/path/to/the/sky'],
        ];
    }

    /**
     * @dataProvider validTypeProvider
     */
    public function testTypecodeProcess($type, $expected)
    {
        $modifier = (new Typecode(FtpPath::TYPE_ASCII))->WithType($type);

        $this->assertSame($expected, $modifier($this->uri)->getPath());
    }

    public function validTypeProvider()
    {
        return [
            [FtpPath::TYPE_BINARY, '/path/to/the/sky.php;type=i'],
            [FtpPath::TYPE_EMPTY, '/path/to/the/sky.php'],
        ];
    }

    public function testWithTrailingSlashProcess()
    {
        $modifier = new AddTrailingSlash();
        $this->assertSame('/path/to/the/sky.php/', $modifier($this->uri)->getPath());
    }

    /**
     * @dataProvider validSlashPresenceProvider
     */
    public function testWithoutLeadingSlashProcess($uri)
    {
        $modifier = new RemoveLeadingSlash();

        $this->assertSame(
            (string) HierarchicalPath::createFromArray($uri->path->toArray()),
            $modifier($uri)->getPath()
        );
    }

    /**
     * @dataProvider validSlashPresenceProvider
     */
    public function testWithLeadingSlashProcess($uri)
    {
        $modifier = new AddLeadingSlash();

        $this->assertSame(
            (string) HierarchicalPath::createFromArray($uri->path->toArray(), HierarchicalPath::IS_ABSOLUTE),
            $modifier($uri)->getPath()
        );
    }

    public function validSlashPresenceProvider()
    {
        $uri = HttpUri::createFromString(
            'http://www.example.com/path/to/the/sky.php?kingkong=toto&foo=bar+baz#doc3'
        );

        return [
            [$uri],
            [$uri->withPath('toto/le heros')],
        ];
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAppendProcessFailed()
    {
        (new AppendSegment(''))->__invoke('http://www.example.com');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testPrependProcessFailed()
    {
        (new PrependSegment(''))->__invoke('http://www.example.com');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithoutDotSegmentsProcessFailed()
    {
        (new RemoveDotSegments())->__invoke('http://www.example.com');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithoutLeadingSlashProcessFailed()
    {
        (new RemoveLeadingSlash())->__invoke('http://www.example.com');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithLeadingSlashProcessFailed()
    {
        (new AddLeadingSlash())->__invoke('http://www.example.com');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithoutTrailingSlashProcessFailed()
    {
        (new AddTrailingSlash())->__invoke('http://www.example.com');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithTrailingSlashProcessFailed()
    {
        (new AddTrailingSlash())->__invoke('http://www.example.com');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testExtensionProcessFailed()
    {
        new Extension(['toto']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testTypecodeProcessFailed()
    {
        new Typecode('toto');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithoutEmptySegmentsProcessFailed()
    {
        (new RemoveEmptySegments())->__invoke('http://www.example.com');
    }
}