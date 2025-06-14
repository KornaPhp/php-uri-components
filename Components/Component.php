<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri\Components;

use League\Uri\Contracts\Conditionable;
use League\Uri\Contracts\UriAccess;
use League\Uri\Contracts\UriComponentInterface;
use League\Uri\Contracts\UriInterface;
use League\Uri\Encoder;
use League\Uri\Exceptions\SyntaxError;
use League\Uri\Uri;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use Stringable;
use Uri\Rfc3986\Uri as Rfc3986Uri;
use Uri\WhatWg\Url as WhatWgUrl;

use function is_bool;
use function preg_match;
use function sprintf;

abstract class Component implements UriComponentInterface, Conditionable
{
    protected const REGEXP_INVALID_URI_CHARS = '/[\x00-\x1f\x7f]/';

    abstract public function value(): ?string;

    public function jsonSerialize(): ?string
    {
        return $this->value();
    }

    public function toString(): string
    {
        return $this->value() ?? '';
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function getUriComponent(): string
    {
        return $this->toString();
    }

    final protected static function filterUri(WhatWgUrl|Rfc3986Uri|Stringable|string $uri): UriInterface|Psr7UriInterface
    {
        return match (true) {
            $uri instanceof UriAccess => $uri->getUri(),
            $uri instanceof Psr7UriInterface, $uri instanceof UriInterface => $uri,
            $uri instanceof WhatWgUrl => Uri::new($uri->toAsciiString()),
            default => Uri::new($uri),
        };
    }

    /**
     * Validate the component content.
     */
    protected function validateComponent(Stringable|int|string|null $component): ?string
    {
        return Encoder::decodeNecessary($component);
    }

    /**
     * Filter the input component.
     *
     * @throws SyntaxError If the component cannot be converted to a string or null
     */
    final protected static function filterComponent(Stringable|int|string|null $component): ?string
    {
        return match (true) {
            $component instanceof UriComponentInterface => $component->value(),
            null === $component => null,
            1 === preg_match(self::REGEXP_INVALID_URI_CHARS, (string) $component) => throw new SyntaxError(sprintf('Invalid component string: %s.', $component)),
            default => (string) $component,
        };
    }

    final public function when(callable|bool $condition, callable $onSuccess, ?callable $onFail = null): static
    {
        if (!is_bool($condition)) {
            $condition = $condition($this);
        }

        return match (true) {
            $condition => $onSuccess($this),
            null !== $onFail => $onFail($this),
            default => $this,
        } ?? $this;
    }
}
