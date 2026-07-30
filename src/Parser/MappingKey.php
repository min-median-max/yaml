<?php

declare(strict_types=1);

namespace Purr\Yaml\Parser;

use function strlen;

/**
 * The two halves of a `key: value` line.
 */
final class MappingKey
{
    public function __construct(
        private readonly string $key,
        private readonly string $value,
    ) {}

    /**
     * Splits the text at the first `:` that is followed by a space or ends the line,
     * ignoring colons inside quotes or flow collections. Returns null when the text
     * does not open a mapping entry.
     */
    public static function fromText(TextHelper $textHelper, string $text): ?self
    {
        $depth = 0;
        $position = 0;
        $length = strlen($text);

        while ($position < $length) {
            $character = $text[$position];

            if ('"' === $character || "'" === $character) {
                $end = $textHelper->indexAfterQuotedString($text, $position);

                if (null === $end) {
                    return null;
                }

                $position = $end;

                continue;
            }

            if ('[' === $character || '{' === $character) {
                ++$depth;
            } elseif (']' === $character || '}' === $character) {
                --$depth;
            } elseif (':' === $character && 0 === $depth) {
                $following = $text[$position + 1] ?? ' ';

                if (' ' === $following || "\t" === $following) {
                    return new self(rtrim(substr($text, 0, $position)), ltrim(substr($text, $position + 1)));
                }
            }

            ++$position;
        }

        return null;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
