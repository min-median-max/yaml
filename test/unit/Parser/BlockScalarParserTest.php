<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Exception\ParseException;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Node\ScalarStyle;
use Purr\Yaml\Node\Tag;
use Purr\Yaml\Parser\BlockScalarParser;
use Purr\Yaml\Parser\LineReader;
use Purr\Yaml\Parser\NodeHeader;

#[CoversClass(BlockScalarParser::class)]
final class BlockScalarParserTest extends TestCase
{
    public function testParse_LiteralBlock_KeepsLineBreaksAndAddsFinalNewline(): void
    {
        $reader = LineReader::fromString("  first\n  second\nnext: 1\n");
        $parser = new BlockScalarParser;

        self::assertEquals(
            new ScalarNode(value: "first\nsecond\n", style: ScalarStyle::Literal, line: 1),
            $parser->parse($reader, '|', 1, NodeHeader::none(), 1),
        );
    }

    public function testParse_FoldedBlock_JoinsContinuationLines(): void
    {
        $reader = LineReader::fromString("  first\n  second\n\n  new paragraph\nnext: 1\n");
        $parser = new BlockScalarParser;

        self::assertEquals(
            new ScalarNode(value: "first second\nnew paragraph\n", style: ScalarStyle::Folded, line: 1),
            $parser->parse($reader, '>', 1, NodeHeader::none(), 1),
        );
    }

    public function testParse_StripIndicator_RemovesTheFinalNewline(): void
    {
        $reader = LineReader::fromString("  first\n  second\nnext: 1\n");
        $parser = new BlockScalarParser;

        self::assertEquals(
            new ScalarNode(value: "first\nsecond", style: ScalarStyle::Literal, line: 1),
            $parser->parse($reader, '|-', 1, NodeHeader::none(), 1),
        );
    }

    public function testParse_KeepIndicator_KeepsTrailingBlankLines(): void
    {
        $reader = LineReader::fromString("  first\n\n\nnext: 1\n");
        $parser = new BlockScalarParser;

        self::assertEquals(
            new ScalarNode(value: "first\n\n\n", style: ScalarStyle::Literal, line: 1),
            $parser->parse($reader, '|+', 1, NodeHeader::none(), 1),
        );
    }

    public function testParse_ExplicitIndentationIndicator_KeepsLeadingSpaces(): void
    {
        $reader = LineReader::fromString("    kept indent\n  outer\nnext: 1\n");
        $parser = new BlockScalarParser;

        self::assertEquals(
            new ScalarNode(value: "  kept indent\nouter\n", style: ScalarStyle::Literal, line: 1),
            $parser->parse($reader, '|2', 1, NodeHeader::none(), 1),
        );
    }

    public function testParse_InvalidHeader_ThrowsParseException(): void
    {
        $reader = LineReader::fromString("  text\n");
        $parser = new BlockScalarParser;

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid block scalar header "|x"');

        $parser->parse($reader, '|x', 1, NodeHeader::none(), 3);
    }

    public function testParse_AnchorAndTag_PreservesMetadata(): void
    {
        $reader = LineReader::fromString("  value\nnext: 1\n");
        $parser = new BlockScalarParser;
        $properties = NodeHeader::extract('&anchor !!str |');

        self::assertEquals(
            new ScalarNode(
                value: "value\n",
                style: ScalarStyle::Literal,
                anchor: 'anchor',
                tag: Tag::fromString('!!str'),
                line: 1,
            ),
            $parser->parse($reader, '|', 1, $properties, 1),
        );
    }
}
