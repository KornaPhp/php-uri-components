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

declare(strict_types=1);

namespace League\Uri\Component;

use League\Uri\Exception\InvalidUriComponent;
use League\Uri\Exception\MalformedUriComponent;
use function defined;
use function explode;
use function filter_var;
use function function_exists;
use function idn_to_ascii;
use function idn_to_utf8;
use function implode;
use function in_array;
use function inet_pton;
use function preg_match;
use function rawurldecode;
use function sprintf;
use function strpos;
use function strtolower;
use function substr;
use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;
use const IDNA_ERROR_BIDI;
use const IDNA_ERROR_CONTEXTJ;
use const IDNA_ERROR_DISALLOWED;
use const IDNA_ERROR_DOMAIN_NAME_TOO_LONG;
use const IDNA_ERROR_EMPTY_LABEL;
use const IDNA_ERROR_HYPHEN_3_4;
use const IDNA_ERROR_INVALID_ACE_LABEL;
use const IDNA_ERROR_LABEL_HAS_DOT;
use const IDNA_ERROR_LABEL_TOO_LONG;
use const IDNA_ERROR_LEADING_COMBINING_MARK;
use const IDNA_ERROR_LEADING_HYPHEN;
use const IDNA_ERROR_PUNYCODE;
use const IDNA_ERROR_TRAILING_HYPHEN;
use const INTL_IDNA_VARIANT_UTS46;

/**
 * Value object representing a URI Host component.
 *
 * Instances of this interface are considered immutable; all methods that
 * might change state MUST be implemented such that they retain the internal
 * state of the current instance and return an instance that contains the
 * changed state.
 *
 * @package    League\Uri
 * @subpackage League\Uri\Component
 * @author     Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since      1.0.0
 * @see        https://tools.ietf.org/html/rfc3986#section-3.2.2
 */
class Host extends Component
{
    /**
     * @internal
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * invalid characters in host regular expression
     */
    const REGEXP_INVALID_HOST_CHARS = '/
        [:\/?#\[\]@ ]  # gen-delims characters as well as the space character
    /ix';

    /**
     * @internal
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * General registered name regular expression
     */
    const REGEXP_REGISTERED_NAME = '/(?(DEFINE)
        (?<unreserved>[a-z0-9_~\-])   # . is missing as it is used to separate labels
        (?<sub_delims>[!$&\'()*+,;=])
        (?<encoded>%[A-F0-9]{2})
        (?<reg_name>(?:(?&unreserved)|(?&sub_delims)|(?&encoded))*)
    )
    ^(?:(?&reg_name)\.)*(?&reg_name)\.?$/ix';

    /**
     * @internal
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * IPvFuture regular expression
     */
    const REGEXP_IP_FUTURE = '/^
        v(?<version>[A-F0-9]+)\.
        (?:
            (?<unreserved>[a-z0-9_~\-\.])|
            (?<sub_delims>[!$&\'()*+,;=:])  # also include the : character
        )+
    $/ix';

    /**
     * @internal
     */
    const REGEXP_GEN_DELIMS = '/[:\/?#\[\]@]/';

    /**
     * @internal
     */
    const ADDRESS_BLOCK = "\xfe\x80";

    /**
     * @var string|null
     */
    protected $component;

    /**
     * @var string|null
     */
    protected $ip_version;

    /**
     * {@inheritdoc}
     */
    public static function __set_state(array $properties): self
    {
        return new static($properties['component']);
    }

    /**
     * @codeCoverageIgnore
     */
    protected static function supportIdnHost(): void
    {
        static $idn_support = null;
        $idn_support = $idn_support ?? function_exists('\idn_to_ascii') && defined('\INTL_IDNA_VARIANT_UTS46');
        if (!$idn_support) {
            throw new InvalidUriComponent('IDN host can not be processed. Verify that ext/intl is installed for IDN support and that ICU is at least version 4.6.');
        }
    }

    /**
     * New instance.
     * @param null|mixed $host
     */
    public function __construct($host = null)
    {
        $host = $this->filterComponent($host);
        $this->parse($host);
    }

    /**
     * Validates the submitted data.
     *
     * @throws MalformedUriComponent If the host is invalid
     */
    protected function parse(string $host = null): void
    {
        $this->ip_version = null;
        $this->component = $host;
        if (null === $host || '' === $host) {
            return;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->ip_version = '4';

            return;
        }

        if ('[' === $host[0] && ']' === substr($host, -1)) {
            $ip_host = substr($host, 1, -1);
            if ($this->isValidIpv6Hostname($ip_host)) {
                $this->ip_version = '6';

                return;
            }

            if (preg_match(self::REGEXP_IP_FUTURE, $ip_host, $matches) && !in_array($matches['version'], ['4', '6'], true)) {
                $this->ip_version = $matches['version'];

                return;
            }

            throw new MalformedUriComponent(sprintf('`%s` is an invalid IP literal format', $host));
        }

        $domain_name = rawurldecode($host);
        if (!preg_match(self::REGEXP_NON_ASCII_PATTERN, $domain_name)) {
            $domain_name = strtolower($domain_name);
        }

        $this->component = $domain_name;
        if (preg_match(self::REGEXP_REGISTERED_NAME, $domain_name)) {
            return;
        }

        if (!preg_match(self::REGEXP_NON_ASCII_PATTERN, $domain_name) || preg_match(self::REGEXP_INVALID_HOST_CHARS, $domain_name)) {
            throw new MalformedUriComponent(sprintf('`%s` is an invalid domain name : the host contains invalid characters', $host));
        }

        self::supportIdnHost();

        $domain_name = idn_to_ascii($domain_name, 0, INTL_IDNA_VARIANT_UTS46, $arr);
        if (false === $domain_name || 0 !== $arr['errors']) {
            throw new MalformedUriComponent(sprintf('`%s` is an invalid domain name : %s', $host, $this->getIDNAErrors($arr['errors'])));
        }

        if (false !== strpos($domain_name, '%')) {
            throw new MalformedUriComponent(sprintf('`%s` is an invalid domain name', $host));
        }

        $this->component = $domain_name;
    }

    /**
     * Retrieves and format IDNA conversion error message.
     *
     * @see http://icu-project.org/apiref/icu4j/com/ibm/icu/text/IDNA.Error.html
     */
    protected function getIDNAErrors(int $error_byte): string
    {
        /**
         * IDNA errors.
         */
        static $idn_errors = [
            IDNA_ERROR_EMPTY_LABEL => 'a non-final domain name label (or the whole domain name) is empty',
            IDNA_ERROR_LABEL_TOO_LONG => 'a domain name label is longer than 63 bytes',
            IDNA_ERROR_DOMAIN_NAME_TOO_LONG => 'a domain name is longer than 255 bytes in its storage form',
            IDNA_ERROR_LEADING_HYPHEN => 'a label starts with a hyphen-minus ("-")',
            IDNA_ERROR_TRAILING_HYPHEN => 'a label ends with a hyphen-minus ("-")',
            IDNA_ERROR_HYPHEN_3_4 => 'a label contains hyphen-minus ("-") in the third and fourth positions',
            IDNA_ERROR_LEADING_COMBINING_MARK => 'a label starts with a combining mark',
            IDNA_ERROR_DISALLOWED => 'a label or domain name contains disallowed characters',
            IDNA_ERROR_PUNYCODE => 'a label starts with "xn--" but does not contain valid Punycode',
            IDNA_ERROR_LABEL_HAS_DOT => 'a label contains a dot=full stop',
            IDNA_ERROR_INVALID_ACE_LABEL => 'An ACE label does not contain a valid label string',
            IDNA_ERROR_BIDI => 'a label does not meet the IDNA BiDi requirements (for right-to-left characters)',
            IDNA_ERROR_CONTEXTJ => 'a label does not meet the IDNA CONTEXTJ requirements',
        ];

        $res = [];
        foreach ($idn_errors as $error => $reason) {
            if ($error_byte & $error) {
                $res[] = $reason;
            }
        }

        return [] === $res ? 'Unknown IDNA conversion error.' : implode(', ', $res).'.';
    }

    /**
     * Validates an Ipv6 as Host.
     *
     * @see http://tools.ietf.org/html/rfc6874#section-2
     * @see http://tools.ietf.org/html/rfc6874#section-4
     */
    protected function isValidIpv6Hostname(string $host): bool
    {
        [$ipv6, $scope] = explode('%', $host, 2) + [1 => null];
        if (null === $scope) {
            return (bool) filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
        }

        $scope = rawurldecode('%'.$scope);
        $packed_ip = (string) inet_pton((string) $ipv6);

        return !preg_match(self::REGEXP_NON_ASCII_PATTERN, $scope)
            && !preg_match(self::REGEXP_GEN_DELIMS, $scope)
            && filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            && substr($packed_ip & self::ADDRESS_BLOCK, 0, 2) === self::ADDRESS_BLOCK;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(): ?string
    {
        return $this->component;
    }

    /**
     * Returns the Host ascii representation.
     */
    public function toAscii(): ?string
    {
        return $this->getContent();
    }

    /**
     * Returns the Host unicode representation.
     */
    public function toUnicode(): ?string
    {
        if (null !== $this->ip_version
            || null === $this->component
            || false === strpos($this->component, 'xn--')
        ) {
            return $this->component;
        }

        return (string) idn_to_utf8($this->component, 0, INTL_IDNA_VARIANT_UTS46);
    }

    /**
     * {@inheritdoc}
     */
    public function withContent($content)
    {
        $content = $this->filterComponent($content);
        if ($content === $this->getContent()) {
            return $this;
        }

        return new self($content);
    }
}