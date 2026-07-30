<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Parser\TextHelper;

#[CoversClass(TextHelper::class)]
final class TextHelperTest extends TestCase
{
    public function testIndexAfterQuotedString_ClosedDoubleQuotedString_ReturnsPositionAfterTheClosingQuote(): void
    {
        $textHelper = new TextHelper;

        self::assertSame(13, $textHelper->indexAfterQuotedString('"line\nbreak"', 0));
    }

    public function testIndexAfterQuotedString_ClosedSingleQuotedString_ReturnsPositionAfterTheClosingQuote(): void
    {
        $textHelper = new TextHelper;

        self::assertSame(7, $textHelper->indexAfterQuotedString("'it''s'", 0));
    }

    public function testIndexAfterQuotedString_UnclosedQuotedString_ReturnsNull(): void
    {
        $textHelper = new TextHelper;

        self::assertNull($textHelper->indexAfterQuotedString('"open', 0));
    }

    public function testStripComment_TrailingComment_RemovesIt(): void
    {
        $textHelper = new TextHelper;

        self::assertSame('value: kept', $textHelper->stripComment('value: kept # dropped'));
    }

    public function testStripComment_HashWithoutASeparatingSpace_KeepsItInTheText(): void
    {
        $textHelper = new TextHelper;

        self::assertSame('value: pass#word', $textHelper->stripComment('value: pass#word'));
    }

    public function testStripComment_HashInsideDoubleQuotes_KeepsItInTheText(): void
    {
        $textHelper = new TextHelper;

        self::assertSame('value: "kept # here"', $textHelper->stripComment('value: "kept # here"'));
    }

    public function testStripComment_HashInsideSingleQuotes_KeepsItInTheText(): void
    {
        $textHelper = new TextHelper;

        self::assertSame("value: 'kept # here'", $textHelper->stripComment("value: 'kept # here'"));
    }

    public function testStripComment_UnclosedQuotedString_ReturnsTheOriginalText(): void
    {
        $textHelper = new TextHelper;

        self::assertSame('value: "open # comment', $textHelper->stripComment('value: "open # comment'));
    }

    public function testIsComplete_BalancedFlowCollection_ReturnsTrue(): void
    {
        $textHelper = new TextHelper;

        self::assertTrue($textHelper->isCompleted('[1, {a: 2}]'));
    }

    public function testIsComplete_UnclosedFlowCollection_ReturnsFalse(): void
    {
        $textHelper = new TextHelper;

        self::assertFalse($textHelper->isCompleted('[1, 2'));
    }

    public function testIsComplete_UnclosedQuotedString_ReturnsFalse(): void
    {
        $textHelper = new TextHelper;

        self::assertFalse($textHelper->isCompleted('value: "open'));
    }

    public function testIsComplete_QuotedBracketsInsideAString_ReturnsTrue(): void
    {
        $textHelper = new TextHelper;

        self::assertTrue($textHelper->isCompleted('value: "[not a collection]"'));
    }
}
