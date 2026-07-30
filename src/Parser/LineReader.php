<?php

declare(strict_types=1);

namespace Purr\Yaml\Parser;

use function count;

use Purr\Yaml\Exception\ParseException;

/**
 * A cursor over the lines of a YAML source.
 */
final class LineReader
{
    /**
     * @param list<Line> $lines
     */
    private function __construct(private array $lines, private int $index = 0) {}

    public static function fromString(string $input): self
    {
        $input = str_replace(["\r\n", "\r"], "\n", $input);
        $input = preg_replace('/^\x{FEFF}/u', '', $input) ?? $input;

        $lines = [];

        foreach (explode("\n", $input) as $number => $raw) {
            $lines[] = new Line($raw, $number + 1);
        }

        return new self($lines);
    }

    public function peek(): ?Line
    {
        return $this->lines[$this->index] ?? null;
    }

    public function next(): ?Line
    {
        $line = $this->lines[$this->index] ?? null;

        if (null !== $line) {
            ++$this->index;
        }

        return $line;
    }

    /**
     * Puts a line in front of the cursor, used to re-read the remainder of a line.
     */
    public function pushBack(Line $line): void
    {
        array_splice($this->lines, $this->index, 0, [$line]);
    }

    /**
     * Moves the cursor to the next line that carries content.
     */
    public function skipIgnorable(): void
    {
        while (null !== ($line = $this->peek())) {
            if (!$line->isBlank() && !$line->isComment()) {
                if ($line->startsWithTab()) {
                    throw new ParseException('Tabs are not allowed for indentation', $line->getNumber(), $line->getText());
                }

                return;
            }

            ++$this->index;
        }
    }

    public function isEof(): bool
    {
        return $this->index >= count($this->lines);
    }
}
