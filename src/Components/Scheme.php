<?php
/**
 * League.Uri (http://uri.thephpleague.com).
 *
 * @package    League\Uri
 * @subpackage League\Uri\Components
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @license    https://github.com/thephpleague/uri-components/blob/master/LICENSE (MIT License)
 * @version    2.0.0
 * @link       https://github.com/thephpleague/uri-components
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace League\Uri\Components;

use League\Uri\ComponentInterface;
use League\Uri\Exception;

/**
 * Value object representing a URI Scheme component.
 *
 * Instances of this interface are considered immutable; all methods that
 * might change state MUST be implemented such that they retain the internal
 * state of the current instance and return an instance that contains the
 * changed state.
 *
 * @package    League\Uri
 * @subpackage League\Uri\Components
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.0.0
 * @see        https://tools.ietf.org/html/rfc3986#section-3.1
 */
final class Scheme implements ComponentInterface
{
    /**
     * @internal
     */
    const ENCODING_LIST = [
        self::RFC1738_ENCODING => 1,
        self::RFC3986_ENCODING => 1,
        self::RFC3987_ENCODING => 1,
        self::NO_ENCODING => 1,
    ];

    /**
     * @var string|null
     */
    private $scheme;

    /**
     * {@inheritdoc}
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['scheme']);
    }

    /**
     * New instance.
     *
     * @param mixed $scheme
     */
    public function __construct($scheme = null)
    {
        $this->scheme = $this->validate($scheme);
    }

    /**
     * Validate a scheme.
     *
     * @param mixed $scheme
     *
     * @throws Exception if the scheme is invalid
     *
     * @return null|string
     */
    private function validate($scheme)
    {
        if ($scheme instanceof ComponentInterface) {
            $scheme = $scheme->getContent();
        }

        if (null === $scheme) {
            return $scheme;
        }

        if (is_object($scheme) && method_exists($scheme, '__toString')) {
            $scheme = (string) $scheme;
        }

        if (is_string($scheme) && preg_match(',^[a-z]([-a-z0-9+.]+)?$,i', $scheme)) {
            return strtolower($scheme);
        }

        throw new Exception(sprintf("Invalid Submitted scheme: '%s'", $scheme));
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(int $enc_type = self::RFC3986_ENCODING)
    {
        if (!isset(self::ENCODING_LIST[$enc_type])) {
            throw new Exception(sprintf('Unsupported or Unknown Encoding: %s', $enc_type));
        }

        return $this->scheme;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string) $this->scheme;
    }

    /**
     * {@inheritdoc}
     */
    public function getUriComponent(): string
    {
        if (null === $this->scheme) {
            return '';
        }

        return $this->scheme.':';
    }

    /**
     * {@inheritdoc}
     */
    public function __debugInfo()
    {
        return [
            'scheme' => $this->scheme,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function withContent($content)
    {
        $content = $this->validate($content);
        if ($content === $this->scheme) {
            return $this;
        }

        $clone = clone $this;
        $clone->scheme = $content;

        return $clone;
    }
}
