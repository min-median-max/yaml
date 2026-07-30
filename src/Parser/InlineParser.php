<?php

declare(strict_types=1);

namespace Purr\Yaml\Parser;

use function chr;

use Purr\Yaml\Exception\ParseException;
use Purr\Yaml\Node\AbstractNode;
use Purr\Yaml\Node\AliasNode;
use Purr\Yaml\Node\CollectionStyle;
use Purr\Yaml\Node\MappingEntry;
use Purr\Yaml\Node\MappingEntryList;
use Purr\Yaml\Node\MappingNode;
use Purr\Yaml\Node\NodeList;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Node\ScalarStyle;
use Purr\Yaml\Node\SequenceNode;

use function sprintf;
use function strlen;

/**
 * Parses everything that lives inside a single (possibly joined) piece of text:
 * flow collections, quoted scalars, aliases and plain scalars.
 */
final class InlineParser
{
    private const string ANCHOR_DELIMITERS = " \t,[]{}";

    private string $text = '';

    private int $cursor = 0;

    private int $depth = 0;

    public function __construct(
        private readonly AnchorRegistry $anchors,
        private readonly TextHelper $textHelper,
        private readonly int $line = 0,
    ) {}

    /**
     * Parses the text as exactly one node.
     */
    public function parse(string $text, ?NodeHeader $properties = null): AbstractNode
    {
        $this->text = $text;
        $this->cursor = 0;
        $this->depth = 0;

        $this->skipSpaces();
        $node = $this->parseNode($properties);
        $this->skipSpaces();

        if (!$this->eof()) {
            throw new ParseException(
                sprintf('Unexpected characters "%s"', substr($this->text, $this->cursor)),
                $this->line,
                $this->text,
            );
        }

        return $node;
    }

    private function parseNode(?NodeHeader $inherited = null, bool $isKey = false): AbstractNode
    {
        $properties = $this->readProperties()->inherit($inherited);

        return match ($this->current()) {
            '[' => $this->parseFlowSequence($properties),
            '{' => $this->parseFlowMapping($properties),
            '*' => $this->parseAlias(),
            '"' => $this->parseQuoted($properties, ScalarStyle::DoubleQuoted),
            "'" => $this->parseQuoted($properties, ScalarStyle::SingleQuoted),
            default => $this->parsePlain($properties, $isKey),
        };
    }

    private function readProperties(): NodeHeader
    {
        $remaining = substr($this->text, $this->cursor);
        $header = NodeHeader::extract($remaining, $this->line);
        $this->cursor += strlen($remaining) - strlen($header->getRest());

        return $header;
    }

    private function parseFlowSequence(NodeHeader $properties): AbstractNode
    {
        ++$this->cursor;
        ++$this->depth;

        $items = [];

        while (true) {
            $this->skipSpaces();

            if ($this->eof()) {
                throw new ParseException('Unterminated flow sequence', $this->line, $this->text);
            }

            if (']' === $this->current()) {
                ++$this->cursor;

                break;
            }

            $items[] = $this->parseSequenceEntry();
            $this->skipSpaces();

            if (',' === $this->current()) {
                ++$this->cursor;

                continue;
            }

            if (']' === $this->current()) {
                ++$this->cursor;

                break;
            }

            throw new ParseException('Expected "," or "]" inside a flow sequence', $this->line, $this->text);
        }

        --$this->depth;

        return $this->anchors->keep(new SequenceNode(
            new NodeList(...$items),
            CollectionStyle::Flow,
            $properties->findAnchor(),
            $properties->findTag(),
            $this->line,
        ));
    }

    /**
     * Handles both `[value]` and the single pair form `[key: value]`.
     */
    private function parseSequenceEntry(): AbstractNode
    {
        $node = $this->parseNode();
        $this->skipSpaces();

        if (':' !== $this->current()) {
            return $node;
        }

        ++$this->cursor;
        $this->skipSpaces();
        $value = $this->isEntryEnd() ? ScalarNode::null($this->line) : $this->parseNode();

        return new MappingNode(new MappingEntryList(new MappingEntry($node, $value)), CollectionStyle::Flow, null, null, $this->line);
    }

    private function parseFlowMapping(NodeHeader $properties): AbstractNode
    {
        ++$this->cursor;
        ++$this->depth;

        $entries = [];

        while (true) {
            $this->skipSpaces();

            if ($this->eof()) {
                throw new ParseException('Unterminated flow mapping', $this->line, $this->text);
            }

            if ('}' === $this->current()) {
                ++$this->cursor;

                break;
            }

            $key = $this->parseNode(null, true);
            $this->skipSpaces();
            $value = ScalarNode::null($this->line);

            if (':' === $this->current()) {
                ++$this->cursor;
                $this->skipSpaces();

                if (!$this->isEntryEnd()) {
                    $value = $this->parseNode();
                }
            }

            $entries[] = new MappingEntry($key, $value);
            $this->skipSpaces();

            if (',' === $this->current()) {
                ++$this->cursor;

                continue;
            }

            if ('}' === $this->current()) {
                ++$this->cursor;

                break;
            }

            throw new ParseException('Expected "," or "}" inside a flow mapping', $this->line, $this->text);
        }

        --$this->depth;

        return $this->anchors->keep(
            new MappingNode(
                new MappingEntryList(...$entries),
                CollectionStyle::Flow,
                $properties->findAnchor(),
                $properties->findTag(),
                $this->line,
            )
        );
    }

    private function parseAlias(): AbstractNode
    {
        ++$this->cursor;
        $length = strcspn($this->text, self::ANCHOR_DELIMITERS, $this->cursor);
        $name = substr($this->text, $this->cursor, $length);

        if ('' === $name) {
            throw new ParseException('A reference must have a name', $this->line, $this->text);
        }

        $this->cursor += $length;

        return new AliasNode($name, $this->anchors->get($name, $this->line), $this->line);
    }

    private function parseQuoted(NodeHeader $properties, ScalarStyle $style): AbstractNode
    {
        $end = $this->textHelper->indexAfterQuotedString($this->text, $this->cursor);

        if (null === $end) {
            throw new ParseException(
                sprintf('Unterminated %s quoted string', ScalarStyle::DoubleQuoted === $style ? 'double' : 'single'),
                $this->line,
                $this->text,
            );
        }

        $raw = substr($this->text, $this->cursor + 1, $end - $this->cursor - 2);
        $this->cursor = $end;

        $value = ScalarStyle::DoubleQuoted === $style
            ? $this->unescape($raw)
            : str_replace("''", "'", $raw);

        return $this->anchors->keep(new ScalarNode(
            $value,
            $style,
            $properties->findAnchor(),
            $properties->findTag(),
            $this->line,
        ));
    }

    private function parsePlain(NodeHeader $properties, bool $isKey): AbstractNode
    {
        $start = $this->cursor;
        $length = strlen($this->text);

        while ($this->cursor < $length) {
            $character = $this->text[$this->cursor];

            if ($this->depth > 0 && (',' === $character || ']' === $character || '}' === $character)) {
                break;
            }

            if (':' === $character && $this->endsPlainScalar($isKey)) {
                break;
            }

            ++$this->cursor;
        }

        $value = rtrim(substr($this->text, $start, $this->cursor - $start));

        return $this->anchors->keep(new ScalarNode(
            $value,
            ScalarStyle::Plain,
            $properties->findAnchor(),
            $properties->findTag(),
            $this->line,
        ));
    }

    private function endsPlainScalar(bool $isKey): bool
    {
        if ($isKey) {
            return true;
        }

        $following = $this->text[$this->cursor + 1] ?? '';

        if ('' === $following || ' ' === $following || "\t" === $following) {
            return true;
        }

        return $this->depth > 0 && (',' === $following || ']' === $following || '}' === $following);
    }

    private function unescape(string $value): string
    {
        if (!str_contains($value, '\\')) {
            return $value;
        }

        $result = '';
        $length = strlen($value);

        for ($position = 0; $position < $length; ++$position) {
            $character = $value[$position];

            if ('\\' !== $character) {
                $result .= $character;

                continue;
            }

            $escape = $value[++$position] ?? '';
            $result .= match ($escape) {
                '0' => "\0",
                'a' => "\x07",
                'b' => "\x08",
                't' => "\t",
                "\t" => "\t",
                'n' => "\n",
                'v' => "\x0B",
                'f' => "\x0C",
                'r' => "\r",
                'e' => "\x1B",
                ' ' => ' ',
                '"' => '"',
                '/' => '/',
                '\\' => '\\',
                'N' => self::toUtf8(0x85),
                '_' => self::toUtf8(0xA0),
                'L' => self::toUtf8(0x2028),
                'P' => self::toUtf8(0x2029),
                'x' => $this->readCodepoint($value, $position, 2),
                'u' => $this->readCodepoint($value, $position, 4),
                'U' => $this->readCodepoint($value, $position, 8),
                default => throw new ParseException(
                    sprintf('Unknown escape sequence "\%s"', $escape),
                    $this->line,
                    $value,
                ),
            };
        }

        return $result;
    }

    private function readCodepoint(string $value, int &$position, int $digits): string
    {
        $hexadecimal = substr($value, $position + 1, $digits);

        if (strlen($hexadecimal) !== $digits || 1 !== preg_match('/^[0-9a-fA-F]+$/', $hexadecimal)) {
            throw new ParseException(
                sprintf('Expected %d hexadecimal digits in an escape sequence', $digits),
                $this->line,
                $value,
            );
        }

        $position += $digits;

        return self::toUtf8((int) hexdec($hexadecimal));
    }

    private static function toUtf8(int $codepoint): string
    {
        if ($codepoint < 0x80) {
            return chr($codepoint);
        }

        if ($codepoint < 0x800) {
            return chr(0xC0 | $codepoint >> 6).chr(0x80 | $codepoint & 0x3F);
        }

        if ($codepoint < 0x10000) {
            return chr(0xE0 | $codepoint >> 12).chr(0x80 | $codepoint >> 6 & 0x3F).chr(0x80 | $codepoint & 0x3F);
        }

        return chr(0xF0 | $codepoint >> 18)
            .chr(0x80 | $codepoint >> 12 & 0x3F)
            .chr(0x80 | $codepoint >> 6 & 0x3F)
            .chr(0x80 | $codepoint & 0x3F);
    }

    private function isEntryEnd(): bool
    {
        $character = $this->current();

        return '' === $character || ',' === $character || ']' === $character || '}' === $character;
    }

    private function current(): string
    {
        return $this->text[$this->cursor] ?? '';
    }

    private function skipSpaces(): void
    {
        while (' ' === $this->current() || "\t" === $this->current()) {
            ++$this->cursor;
        }
    }

    private function eof(): bool
    {
        return $this->cursor >= strlen($this->text);
    }
}
