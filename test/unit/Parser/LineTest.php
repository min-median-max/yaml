<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Parser\Line;

#[CoversClass(Line::class)]
final class LineTest extends TestCase
{
    public function testGetRaw_Line_ReturnsTheOriginalText(): void
    {
        $line = new Line('  key: value  ', 4);

        self::assertSame('  key: value  ', $line->getRaw());
    }

    public function testGetNumber_Line_ReturnsTheLineNumber(): void
    {
        $line = new Line('  key: value  ', 4);

        self::assertSame(4, $line->getNumber());
    }

    public function testGetIndent_IndentedLine_ReturnsTheIndent(): void
    {
        $line = new Line('  key: value  ', 4);

        self::assertSame(2, $line->getIndent());
    }

    public function testGetText_Line_ReturnsTheTrimmedContent(): void
    {
        $line = new Line('  key: value  ', 4);

        self::assertSame('key: value', $line->getText());
    }

    public function testIsBlank_DetectsEmptyLines(): void
    {
        self::assertTrue((new Line('   ', 1))->isBlank());
        self::assertFalse((new Line('key: value', 1))->isBlank());
    }

    public function testIsComment_DetectsCommentLines(): void
    {
        self::assertTrue((new Line('# comment', 1))->isComment());
        self::assertFalse((new Line('key: value', 1))->isComment());
    }

    public function testStartsWithTab_DetectsTabIndentation(): void
    {
        self::assertTrue((new Line("\tkey: value", 1))->startsWithTab());
        self::assertFalse((new Line('  key: value', 1))->startsWithTab());
    }

    public function testIsDocumentStart_DocumentMarker_ReturnsTrue(): void
    {
        self::assertTrue((new Line('---', 1))->isDocumentStart());
        self::assertTrue((new Line('--- document', 1))->isDocumentStart());
        self::assertFalse((new Line('  ---', 1))->isDocumentStart());
    }

    public function testIsDocumentEnd_DocumentMarker_ReturnsTrue(): void
    {
        self::assertTrue((new Line('...', 1))->isDocumentEnd());
        self::assertTrue((new Line('... end', 1))->isDocumentEnd());
    }

    public function testIsDocumentBoundary_DocumentMarker_ReturnsTrue(): void
    {
        self::assertTrue((new Line('---', 1))->isDocumentBoundary());
    }

    public function testIsDirective_DetectsDirectiveLines(): void
    {
        self::assertTrue((new Line('%YAML 1.2', 1))->isDirective());
        self::assertFalse((new Line('  %YAML 1.2', 1))->isDirective());
        self::assertFalse((new Line('key: value', 1))->isDirective());
    }

    public function testSlice_ReplacesLeadingCharactersWithSpaces(): void
    {
        $line = new Line('- key: value', 2);
        $sliced = $line->slice(2);

        self::assertSame('  key: value', $sliced->getRaw());
        self::assertSame(2, $sliced->getNumber());
        self::assertSame('key: value', $sliced->getText());
    }
}
