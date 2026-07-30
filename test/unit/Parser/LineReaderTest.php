<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Exception\ParseException;
use Purr\Yaml\Parser\LineReader;

#[CoversClass(LineReader::class)]
final class LineReaderTest extends TestCase
{
    public function testFromString_NormalizesLineEndingsAndBom(): void
    {
        $reader = LineReader::fromString("\xEF\xBB\xBFkey: value\r\nnext: 1\n");

        self::assertSame('key: value', $reader->peek()?->getText());
        self::assertSame(1, $reader->peek()?->getNumber());
        self::assertSame('key: value', $reader->next()?->getText());
        self::assertSame('next: 1', $reader->peek()?->getText());
    }

    public function testNext_AdvancesTheCursor(): void
    {
        $reader = LineReader::fromString("first\nsecond");

        self::assertSame('first', $reader->next()?->getText());
        self::assertSame('second', $reader->next()?->getText());
        self::assertNull($reader->peek());
        self::assertTrue($reader->isEof());
    }

    public function testPushBack_ReinsertsALineBeforeTheCursor(): void
    {
        $reader = LineReader::fromString("first\nsecond\n");
        $first = $reader->next();
        self::assertNotNull($first);

        $reader->pushBack($first);

        self::assertSame('first', $reader->peek()?->getText());
        self::assertSame('first', $reader->next()?->getText());
        self::assertSame('second', $reader->next()?->getText());
    }

    public function testSkipIgnorable_SkipsBlankLinesAndComments(): void
    {
        $reader = LineReader::fromString("\n# comment\n\nkey: value\n");

        $reader->skipIgnorable();

        self::assertSame('key: value', $reader->peek()?->getText());
    }

    public function testSkipIgnorable_TabIndentedLine_ThrowsParseException(): void
    {
        $reader = LineReader::fromString("\tkey: value\n");
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Tabs are not allowed');

        $reader->skipIgnorable();
    }
}
