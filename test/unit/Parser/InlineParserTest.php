<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Exception\ParseException;
use Purr\Yaml\Node\AliasNode;
use Purr\Yaml\Node\CollectionStyle;
use Purr\Yaml\Node\MappingEntry;
use Purr\Yaml\Node\MappingEntryList;
use Purr\Yaml\Node\MappingNode;
use Purr\Yaml\Node\NodeList;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Node\ScalarStyle;
use Purr\Yaml\Node\SequenceNode;
use Purr\Yaml\Node\Tag;
use Purr\Yaml\Parser\AnchorRegistry;
use Purr\Yaml\Parser\InlineParser;
use Purr\Yaml\Parser\NodeHeader;
use Purr\Yaml\Parser\TextHelper;

#[CoversClass(InlineParser::class)]
final class InlineParserTest extends TestCase
{
    public function testParse_FlowSequence_ReturnsAList(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new SequenceNode(
                new NodeList(
                    new ScalarNode(value: '1', line: 1),
                    new ScalarNode(value: '2', line: 1),
                    new ScalarNode(value: '3', line: 1),
                ),
                style: CollectionStyle::Flow,
                line: 1,
            ),
            $parser->parse('[1, 2, 3]'),
        );
    }

    public function testParse_FlowMapping_ReturnsAMapping(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new MappingNode(
                entries: new MappingEntryList(
                    new MappingEntry(
                        key: new ScalarNode(value: 'x', line: 1),
                        value: new ScalarNode(value: '1', line: 1),
                    ),
                    new MappingEntry(
                        key: new ScalarNode(value: 'y', line: 1),
                        value: new ScalarNode(value: '2', line: 1),
                    ),
                ),
                style: CollectionStyle::Flow,
                line: 1,
            ),
            $parser->parse('{x: 1, y: 2}'),
        );
    }

    public function testParse_EmptyFlowCollections_ReturnsEmptyCollections(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new SequenceNode(new NodeList, style: CollectionStyle::Flow, line: 1),
            $parser->parse('[]'),
        );
        self::assertEquals(
            new MappingNode(entries: new MappingEntryList, style: CollectionStyle::Flow, line: 1),
            $parser->parse('{}'),
        );
    }

    public function testParse_DoubleQuotedScalar_UnescapesCharacters(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new ScalarNode(value: "line\nbreak", style: ScalarStyle::DoubleQuoted, line: 1),
            $parser->parse('"line\nbreak"'),
        );
    }

    public function testParse_SingleQuotedScalar_EscapesQuotes(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new ScalarNode(value: "it's", style: ScalarStyle::SingleQuoted, line: 1),
            $parser->parse("'it''s'"),
        );
    }

    public function testParse_PlainScalar_ReturnsTheText(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new ScalarNode(value: 'plain', style: ScalarStyle::Plain, line: 1),
            $parser->parse('plain'),
        );
    }

    public function testParse_Alias_ReturnsTheReference(): void
    {
        $anchors = new AnchorRegistry;
        $target = new ScalarNode(value: '42', anchor: 'value', line: 1);
        $anchors->register('value', $target);
        $parser = new InlineParser($anchors, new TextHelper, 2);

        self::assertEquals(
            new AliasNode(name: 'value', target: $target, line: 2),
            $parser->parse('*value'),
        );
    }

    public function testParse_InheritedProperties_AppliesAnchorAndTag(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);
        $properties = NodeHeader::extract('&item !!str');

        self::assertEquals(
            new SequenceNode(
                new NodeList(new ScalarNode(value: '1', line: 1)),
                style: CollectionStyle::Flow,
                anchor: 'item',
                tag: Tag::fromString('!!str'),
                line: 1,
            ),
            $parser->parse('[1]', $properties),
        );
    }

    public function testParse_UnexpectedTrailingCharacters_ThrowsParseException(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unexpected characters "trailing"');

        $parser->parse('[1, 2] trailing');
    }

    public function testParse_UnterminatedFlowSequence_ThrowsParseException(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Expected "," or "]" inside a flow sequence');

        $parser->parse('[1, 2');
    }

    public function testParse_UnterminatedQuotedString_ThrowsParseException(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unterminated double quoted string');

        $parser->parse('"open');
    }

    public function testParse_UnterminatedSingleQuotedString_ThrowsParseException(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unterminated single quoted string');

        $parser->parse("'open");
    }

    public function testParse_FlowSequenceWithMappingEntry_ReturnsASinglePairItem(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new SequenceNode(
                new NodeList(
                    new MappingNode(
                        entries: new MappingEntryList(
                            new MappingEntry(
                                key: new ScalarNode(value: 'a', line: 1),
                                value: new ScalarNode(value: '1', line: 1),
                            ),
                        ),
                        style: CollectionStyle::Flow,
                        line: 1,
                    ),
                ),
                style: CollectionStyle::Flow,
                line: 1,
            ),
            $parser->parse('[a: 1]'),
        );
    }

    public function testParse_FlowSequenceEntryWithoutAValue_ReturnsNullForThatEntry(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new SequenceNode(
                new NodeList(
                    new MappingNode(
                        entries: new MappingEntryList(
                            new MappingEntry(
                                key: new ScalarNode(value: 'a', line: 1),
                                value: new ScalarNode(value: '', line: 1),
                            ),
                        ),
                        style: CollectionStyle::Flow,
                        line: 1,
                    ),
                ),
                style: CollectionStyle::Flow,
                line: 1,
            ),
            $parser->parse('[a:]'),
        );
    }

    public function testParse_UnterminatedFlowSequenceAtEnd_ThrowsParseException(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unterminated flow sequence');

        $parser->parse('[');
    }

    public function testParse_FlowMappingEntryWithoutAValue_ReturnsNullForThatEntry(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new MappingNode(
                entries: new MappingEntryList(
                    new MappingEntry(
                        key: new ScalarNode(value: 'a', line: 1),
                        value: new ScalarNode(value: '', line: 1),
                    ),
                    new MappingEntry(
                        key: new ScalarNode(value: 'b', line: 1),
                        value: new ScalarNode(value: '1', line: 1),
                    ),
                ),
                style: CollectionStyle::Flow,
                line: 1,
            ),
            $parser->parse('{a, b: 1}'),
        );
    }

    public function testParse_FlowSequenceValueEndingBeforeComma_StopsAtTheColon(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new SequenceNode(
                new NodeList(
                    new MappingNode(
                        entries: new MappingEntryList(
                            new MappingEntry(
                                key: new ScalarNode(value: 'http', line: 1),
                                value: new ScalarNode(value: '', line: 1),
                            ),
                        ),
                        style: CollectionStyle::Flow,
                        line: 1,
                    ),
                    new ScalarNode(value: 'tail', line: 1),
                ),
                style: CollectionStyle::Flow,
                line: 1,
            ),
            $parser->parse('[http:, tail]'),
        );
    }

    public function testParse_UnterminatedFlowMapping_ThrowsParseException(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unterminated flow mapping');

        $parser->parse('{');
    }

    public function testParse_InvalidFlowMapping_ThrowsParseException(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Expected "," or "}" inside a flow mapping');

        $parser->parse('{a: 1 b: 2}');
    }

    public function testParse_AliasWithoutAName_ThrowsParseException(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('A reference must have a name');

        $parser->parse('*');
    }

    public function testParse_DoubleQuotedScalar_UnescapesCommonSequences(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new ScalarNode(
                value: "\0\x07\x08\t\x0B\x0C\r\x1B /\\\"",
                style: ScalarStyle::DoubleQuoted,
                line: 1,
            ),
            $parser->parse(<<<'YAML'
"\0\a\b\t\v\f\r\e \/\\\""
YAML),
        );
    }

    public function testParse_DoubleQuotedScalar_UnescapesUnicodeCodepoints(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new ScalarNode(
                value: "\u{85}\u{A0}\u{2028}\u{2029}\u{10000}",
                style: ScalarStyle::DoubleQuoted,
                line: 1,
            ),
            $parser->parse('"\N\_\L\P\U00010000"'),
        );
    }

    public function testParse_DoubleQuotedScalar_UnescapesHexadecimalCodepoints(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new ScalarNode(value: 'A', style: ScalarStyle::DoubleQuoted, line: 1),
            $parser->parse('"\x41"'),
        );
        self::assertEquals(
            new ScalarNode(value: 'A', style: ScalarStyle::DoubleQuoted, line: 1),
            $parser->parse('"\u0041"'),
        );
        self::assertEquals(
            new ScalarNode(value: 'A', style: ScalarStyle::DoubleQuoted, line: 1),
            $parser->parse('"\U00000041"'),
        );
    }

    public function testParse_UnknownEscapeSequence_ThrowsParseException(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unknown escape sequence "\q"');

        $parser->parse('"bad \q escape"');
    }

    public function testParse_DoubleQuotedScalar_WithoutEscapes_ReturnsTheTextAsIs(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new ScalarNode(value: 'plain', style: ScalarStyle::DoubleQuoted, line: 1),
            $parser->parse('"plain"'),
        );
    }

    public function testParse_DoubleQuotedScalar_UnescapesTabAfterBackslash(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        self::assertEquals(
            new ScalarNode(value: "a\tb", style: ScalarStyle::DoubleQuoted, line: 1),
            $parser->parse("\"a\\\tb\""),
        );
    }

    public function testParse_InvalidHexEscapeSequence_ThrowsParseException(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Expected 2 hexadecimal digits in an escape sequence');

        $parser->parse('"\xGG"');
    }

    public function testParse_InvalidUnicodeEscapeSequence_ThrowsParseException(): void
    {
        $parser = new InlineParser(new AnchorRegistry, new TextHelper, 1);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Expected 4 hexadecimal digits in an escape sequence');

        $parser->parse('"\uGGGG"');
    }
}
