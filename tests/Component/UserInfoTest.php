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

use League\Uri\Component\UserInfo;
use League\Uri\Exception\InvalidUriComponent;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * @group userinfo
 * @coversDefaultClass \League\Uri\Component\UserInfo
 */
class UserInfoTest extends TestCase
{
    /**
     * @dataProvider userInfoProvider
     * @param string|null $expected_user
     * @param string|null $expected_pass
     * @param string      $expected_str
     * @param string      $iri_str
     * @covers ::__construct
     * @covers ::validateComponent
     * @covers ::getContent
     * @covers ::decoded
     * @covers ::__toString
     * @covers ::decodeMatches
     * @covers ::encodeMatches
     * @covers ::getPass
     * @covers ::getUser
     * @covers ::encodeComponent
     */
    public function testConstructor(
        $user,
        $pass,
        $expected_user,
        $expected_pass,
        $expected_str,
        $iri_str
    ) {
        $userinfo = new UserInfo($user, $pass);
        self::assertSame($expected_user, $userinfo->getUser());
        self::assertSame($expected_pass, $userinfo->getPass());
        self::assertSame($expected_str, (string) $userinfo);
        self::assertSame($iri_str, $userinfo->decoded());
    }

    public function userInfoProvider()
    {
        return [
            [
                'user' => new UserInfo('login'),
                'pass' => new UserInfo('pass'),
                'expected_user' => 'login',
                'expected_pass' => 'pass',
                'expected_str' => 'login:pass',
                'iri_str' => 'login:pass',
            ],
            [
                'user' => 'login',
                'pass' => 'pass',
                'expected_user' => 'login',
                'expected_pass' => 'pass',
                'expected_str' => 'login:pass',
                'iri_str' => 'login:pass',
            ],
            [
                'user' => 'login%61',
                'pass' => 'pass',
                'expected_user' => 'login%61',
                'expected_pass' => 'pass',
                'expected_str' => 'login%61:pass',
                'iri_str' => 'login%61:pass',
            ],
            [
                'user' => 'login',
                'pass' => null,
                'expected_user' => 'login',
                'expected_pass' => null,
                'expected_str' => 'login',
                'iri_str' => 'login',
            ],
            [
                'user' => null,
                'pass' => null,
                'expected_user' => null,
                'expected_pass' => null,
                'expected_str' => '',
                'iri_str' => null,
            ],
            [
                'user' => '',
                'pass' => null,
                'expected_user' => '',
                'expected_pass' => null,
                'expected_str' => '',
                'iri_str' => '',
            ],
            [
                'user' => '',
                'pass' => '',
                'expected_user' => '',
                'expected_pass' => null,
                'expected_str' => '',
                'iri_str' => '',
            ],
            [
                'user' => null,
                'pass' => 'pass',
                'expected_user' => null,
                'expected_pass' => null,
                'expected_str' => '',
                'iri_str' => null,
            ],
            [
                'user' => 'foò',
                'pass' => 'bar',
                'expected_user' => 'foò',
                'expected_pass' => 'bar',
                'expected_str' => 'fo%C3%B2:bar',
                'iri_str' => 'foò:bar',
            ],
            [
                'user' => 'fo+o',
                'pass' => 'ba+r',
                'expected_user' => 'fo+o',
                'expected_pass' => 'ba+r',
                'expected_str' => 'fo+o:ba+r',
                'iri_str' => 'fo+o:ba+r',
            ],

        ];
    }

    /**
     * @dataProvider createFromStringProvider
     * @param string $expected_str
     * @covers ::withContent
     * @covers ::getUser
     * @covers ::getPass
     * @covers ::decodeMatches
     */
    public function testWithContent($user, $str, $expected_user, $expected_pass, $expected_str)
    {
        $conn = (new UserInfo($user))->withContent($str);
        self::assertSame($expected_user, $conn->getUser());
        self::assertSame($expected_pass, $conn->getPass());
        self::assertSame($expected_str, (string) $conn);
    }

    public function createFromStringProvider()
    {
        return [
            'simple' => [null, 'user:pass', 'user', 'pass', 'user:pass'],
            'empty password' => [null, 'user:', 'user', '', 'user:'],
            'no password' => [null, 'user', 'user', null, 'user'],
            'no login but has password' => [null, ':pass', '', null, ''],
            'empty all' => [null, '', '', null, ''],
            'null content' => [null, null, null, null, ''],
            'encoded chars' => [null, 'foo%40bar:bar%40foo', 'foo@bar', 'bar@foo', 'foo%40bar:bar%40foo'],
            'component interface' => [null, new UserInfo('user', 'pass'), 'user', 'pass', 'user:pass'],
            'reset object' => ['login', new UserInfo(null), null, null, ''],
        ];
    }

    /**
     * @covers ::withContent
     * @covers ::decodeMatches
     */
    public function testWithContentReturnSameInstance()
    {
        $conn = new UserInfo();
        self::assertEquals($conn, $conn->withContent(':pass'));

        $conn = new UserInfo('user', 'pass');
        self::assertSame($conn, $conn->withContent('user:pass'));
    }

    /**
     * @covers ::__set_state
     */
    public function testSetState()
    {
        $conn = new UserInfo('user', 'pass');
        $generateConn = eval('return '.var_export($conn, true).';');
        self::assertEquals($conn, $generateConn);
    }

    /**
     * @dataProvider withUserInfoProvider
     * @param string $expected
     * @covers ::withUserInfo
     * @covers ::decodeMatches
     */
    public function testWithUserInfo($user, $pass, $expected)
    {
        self::assertSame($expected, (string) (new UserInfo('user', 'pass'))->withUserInfo($user, $pass));
    }

    public function withUserInfoProvider()
    {
        return [
            'simple' => ['user', 'pass', 'user:pass'],
            'empty password' => ['user', '', 'user:'],
            'no password' => ['user', null, 'user'],
            'no login but has password' => ['', 'pass', ''],
            'empty all' => ['', '', ''],
        ];
    }

    /**
     * @covers ::withContent
     */
    public function testWithContentThrowsInvalidUriComponentException()
    {
        self::expectException(TypeError::class);
        (new UserInfo())->withContent(date_create());
    }

    public function testConstructorThrowsTypeError()
    {
        self::expectException(TypeError::class);
        new UserInfo(date_create());
    }

    public function testConstructorThrowsException()
    {
        self::expectException(InvalidUriComponent::class);
        new UserInfo("\0");
    }
}