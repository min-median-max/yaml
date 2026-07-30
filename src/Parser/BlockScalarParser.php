<?php

declare(strict_types=1);

namespace Purr\Yaml\Parser;

use Purr\Yaml\Exception\ParseException;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Node\ScalarStyle;

use function sprintf;

/**
 * Reads literal (`|`) and folded (`>`) block scalars, including their indentation
 * indicator and chomping indicator.
 */
final class BlockScalarParser
{
    /** Default chomping: strip trailing blank lines, then append a single newline when the body is non-empty. */
    private const string CHOMP_CLIP = 'clip';

    /** Strip chomping (`-` indicator): remove the final newline from the scalar body. */
    private const string CHOMP_STRIP = 'strip';

    /** Keep chomping (`+` indicator): preserve every trailing blank line from the source block. */
    private const string CHOMP_KEEP = 'keep';

    /**
     * @param int $minIndent lowest indentation the content of the scalar may have
     */
    public function parse(LineReader $reader, string $header, int $minIndent, NodeHeader $properties, int $line): ScalarNode
    {
        $style = '|' === $header[0] ? ScalarStyle::Literal : ScalarStyle::Folded;
        [$chomping, $indicator] = $this->parseHeader(substr($header, 1), $line, $header);

        $contentIndent = null === $indicator ? null : max(0, $minIndent - 1) + $indicator;
        $lines = $this->readLines($reader, $minIndent, $contentIndent);

        $trailing = 0;

        while ([] !== $lines && '' === end($lines)) {
            array_pop($lines);
            ++$trailing;
        }

        $body = ScalarStyle::Literal === $style ? implode("\n", $lines) : $this->fold($lines);

        return new ScalarNode(
            $this->chomp($body, $chomping, $trailing),
            $style,
            $properties->findAnchor(),
            $properties->findTag(),
            $line,
        );
    }

    /**
     * @return array{string, null|int}
     */
    private function parseHeader(string $header, int $line, string $full): array
    {
        $chomping = self::CHOMP_CLIP;
        $indicator = null;

        foreach ('' === $header ? [] : str_split($header) as $character) {
            if ('-' === $character) {
                $chomping = self::CHOMP_STRIP;

                continue;
            }

            if ('+' === $character) {
                $chomping = self::CHOMP_KEEP;

                continue;
            }

            if ($character >= '1' && $character <= '9') {
                $indicator = (int) $character;

                continue;
            }

            throw new ParseException(sprintf('Invalid block scalar header "%s"', $full), $line, $full);
        }

        return [$chomping, $indicator];
    }

    /**
     * @return list<string>
     */
    private function readLines(LineReader $reader, int $minIndent, ?int $contentIndent): array
    {
        $lines = [];

        while (null !== ($line = $reader->peek())) {
            if ($line->isBlank()) {
                $lines[] = '';
                $reader->next();

                continue;
            }

            if ($line->getIndent() < $minIndent || $line->isDocumentBoundary()) {
                break;
            }

            $contentIndent ??= $line->getIndent();

            if ($line->getIndent() < $contentIndent) {
                break;
            }

            $lines[] = substr($line->getRaw(), $contentIndent);
            $reader->next();
        }

        return null === $contentIndent ? [] : $lines;
    }

    /**
     * @param list<string> $lines
     */
    private function fold(array $lines): string
    {
        $result = '';
        $previous = null;

        foreach ($lines as $line) {
            $type = match (true) {
                '' === $line => 'empty',
                str_starts_with($line, ' ') => 'indented',
                default => 'normal',
            };

            $result .= match (true) {
                null === $previous => $line,
                'empty' === $type => "\n",
                'empty' === $previous => $line,
                'indented' === $type || 'indented' === $previous => "\n".$line,
                default => ' '.$line,
            };

            $previous = $type;
        }

        return $result;
    }

    private function chomp(string $body, string $chomping, int $trailing): string
    {
        if (self::CHOMP_STRIP === $chomping) {
            return $body;
        }

        if (self::CHOMP_KEEP === $chomping) {
            return '' === $body ? str_repeat("\n", $trailing) : $body.str_repeat("\n", $trailing + 1);
        }

        return '' === $body ? '' : $body."\n";
    }
}
