<?php

declare(strict_types=1);

namespace Purr\Yaml\Parser;

use function strlen;

/**
 * Quote aware helpers shared by the line based parts of the parser.
 */
final class TextHelper
{
    /**
     * Position right after the closing quote, or null when the string is left open.
     */
    public function indexAfterQuotedString(string $text, int $index): ?int
    {
        $quote = $text[$index];
        $length = strlen($text);
        $position = $index + 1;

        while ($position < $length) {
            $character = $text[$position];

            if ('"' === $quote && '\\' === $character) {
                $position += 2;

                continue;
            }

            if ($character === $quote) {
                if ("'" === $quote && "'" === ($text[$position + 1] ?? '')) {
                    $position += 2;

                    continue;
                }

                return $position + 1;
            }

            ++$position;
        }

        return null;
    }

    /**
     * Removes a trailing `# comment`, ignoring hashes inside quotes.
     */
    public function stripComment(string $text): string
    {
        $length = strlen($text);
        $position = 0;

        while ($position < $length) {
            $character = $text[$position];

            if ('"' === $character || "'" === $character) {
                $end = $this->indexAfterQuotedString($text, $position);

                if (null === $end) {
                    return $text;
                }

                $position = $end;

                continue;
            }

            if ('#' === $character && (0 === $position || ' ' === $text[$position - 1] || "\t" === $text[$position - 1])) {
                return rtrim(substr($text, 0, $position));
            }

            ++$position;
        }

        return $text;
    }

    /**
     * Whether every quote and flow bracket opened in the text is also closed.
     */
    public function isCompleted(string $text): bool
    {
        $depth = 0;
        $position = 0;
        $length = strlen($text);

        while ($position < $length) {
            $character = $text[$position];

            if ('"' === $character || "'" === $character) {
                $end = $this->indexAfterQuotedString($text, $position);

                if (null === $end) {
                    return false;
                }

                $position = $end;

                continue;
            }

            if ('[' === $character || '{' === $character) {
                ++$depth;
            } elseif (']' === $character || '}' === $character) {
                --$depth;
            }

            ++$position;
        }

        return $depth <= 0;
    }
}
