<?php

declare(strict_types=1);

namespace Purr\Yaml\Parser;

use Purr\Yaml\Exception\ParseException;
use Purr\Yaml\Node\Tag;

use function strlen;

/**
 * The `&anchor` and `!tag` properties written in front of a node, together with the
 * text that is left once they are consumed.
 */
final class NodeHeader
{
    private const string TOKEN_DELIMITERS = " \t,[]{}";

    private function __construct(
        private readonly ?string $anchor,
        private readonly ?Tag $tag,
        private readonly string $rest,
    ) {}

    public static function none(string $rest = ''): self
    {
        return new self(null, null, $rest);
    }

    public static function extract(string $text, int $line = 0): self
    {
        $anchor = null;
        $tag = null;
        $rest = $text;

        while ('' !== $rest) {
            $first = $rest[0];

            if ('&' !== $first && '!' !== $first) {
                break;
            }

            $token = self::readToken($rest);

            if ('&' === $first) {
                $name = substr($token, 1);

                if ('' === $name) {
                    throw new ParseException('An anchor must have a name', $line, $text);
                }

                $anchor = $name;
            } else {
                $tag = Tag::fromString($token);
            }

            $rest = ltrim(substr($rest, strlen($token)));
        }

        return new self($anchor, $tag, $rest);
    }

    public function findAnchor(): ?string
    {
        return $this->anchor;
    }

    public function findTag(): ?Tag
    {
        return $this->tag;
    }

    public function getRest(): string
    {
        return $this->rest;
    }

    public function isEmpty(): bool
    {
        return null === $this->anchor && null === $this->tag;
    }

    /**
     * Combines the properties of an outer node with these ones, which win on conflicts.
     */
    public function inherit(?self $outer): self
    {
        if (null === $outer) {
            return $this;
        }

        return new self(
            $this->anchor ?? $outer->anchor,
            $this->tag ?? $outer->tag,
            $this->rest,
        );
    }

    public function withRest(string $rest): self
    {
        return new self($this->anchor, $this->tag, $rest);
    }

    private static function readToken(string $text): string
    {
        if (str_starts_with($text, '!<')) {
            $end = strpos($text, '>');

            if (false !== $end) {
                return substr($text, 0, $end + 1);
            }
        }

        $length = strcspn($text, self::TOKEN_DELIMITERS, 1) + 1;

        return substr($text, 0, $length);
    }
}
