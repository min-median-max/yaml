<?php

declare(strict_types=1);

namespace Purr\Yaml\Parser;

use function strlen;

/**
 * A single physical line of the source, aware of its own indentation.
 */
final class Line
{
    private ?int $indent = null;

    private ?string $text = null;

    public function __construct(
        private readonly string $raw,
        private readonly int $number,
    ) {}

    public function getRaw(): string
    {
        return $this->raw;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * Number of leading spaces; blank lines report the indentation of an empty line.
     */
    public function getIndent(): int
    {
        return $this->indent ??= strlen($this->raw) - strlen(ltrim($this->raw, ' '));
    }

    /**
     * The line content without indentation and trailing whitespace.
     */
    public function getText(): string
    {
        return $this->text ??= rtrim(substr($this->raw, $this->getIndent()));
    }

    public function isBlank(): bool
    {
        return '' === trim($this->raw);
    }

    public function isComment(): bool
    {
        return str_starts_with($this->getText(), '#');
    }

    public function startsWithTab(): bool
    {
        return str_starts_with(ltrim($this->raw, ' '), "\t");
    }

    public function isDocumentStart(): bool
    {
        return 0 === $this->getIndent() && ('---' === $this->getText() || str_starts_with($this->getText(), '--- '));
    }

    public function isDocumentEnd(): bool
    {
        return 0 === $this->getIndent() && ('...' === $this->getText() || str_starts_with($this->getText(), '... '));
    }

    public function isDocumentBoundary(): bool
    {
        return $this->isDocumentStart() || $this->isDocumentEnd();
    }

    public function isDirective(): bool
    {
        return 0 === $this->getIndent() && str_starts_with($this->getText(), '%');
    }

    /**
     * Returns the same line with everything before the offset replaced by spaces, which
     * turns `- key: value` into `  key: value` while keeping the original line number.
     */
    public function slice(int $offset): self
    {
        return new self(str_repeat(' ', $offset).substr($this->raw, $offset), $this->number);
    }
}
