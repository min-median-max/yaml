<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Exception\ParseException;
use Purr\Yaml\Parser\NodeHeader;
use Purr\Yaml\Test\Exporter\Parser\NodeHeaderExporter;

#[CoversClass(NodeHeader::class)]
final class NodeHeaderTest extends TestCase
{
    public function testNone_WithoutRest_ReturnsHeaderWithEmptyProperties(): void
    {
        $exporter = new NodeHeaderExporter;
        $header = NodeHeader::none();

        self::assertSame(
            ['anchor' => null, 'tag' => null, 'rest' => ''],
            $exporter->export($header),
        );
    }

    public function testNone_WithRest_KeepsTheRemainingText(): void
    {
        $exporter = new NodeHeaderExporter;
        $header = NodeHeader::none('value');

        self::assertSame(
            ['anchor' => null, 'tag' => null, 'rest' => 'value'],
            $exporter->export($header),
        );
    }

    #[DataProvider('emptyHeaders')]
    public function testIsEmpty_Header_ReturnsExpectedValue(callable $headerFactory, bool $expected): void
    {
        self::assertSame($expected, $headerFactory()->isEmpty());
    }

    /**
     * @return iterable<string, array{callable(): NodeHeader, bool}>
     */
    public static function emptyHeaders(): iterable
    {
        yield 'none without rest' => [static fn (): NodeHeader => NodeHeader::none(), true];
        yield 'none with rest' => [static fn (): NodeHeader => NodeHeader::none('value'), true];
        yield 'extract without properties' => [static fn (): NodeHeader => NodeHeader::extract('key: value'), true];
        yield 'extract with an anchor' => [static fn (): NodeHeader => NodeHeader::extract('&anchor value'), false];
        yield 'extract with a tag' => [static fn (): NodeHeader => NodeHeader::extract('!!int 42'), false];
        yield 'extract with anchor and tag' => [static fn (): NodeHeader => NodeHeader::extract('&defaults !!str value'), false];
    }

    #[DataProvider('extractedHeaders')]
    public function testExtract_Text_ExportsTheExpectedHeader(string $text, array $expected): void
    {
        $exporter = new NodeHeaderExporter;

        self::assertSame($expected, $exporter->export(NodeHeader::extract($text)));
    }

    /**
     * @return iterable<string, array{string, array{anchor: ?string, tag: ?string, rest: string}}>
     */
    public static function extractedHeaders(): iterable
    {
        yield 'text without properties' => [
            'key: value',
            ['anchor' => null, 'tag' => null, 'rest' => 'key: value'],
        ];

        yield 'text with an anchor' => [
            '&anchor value',
            ['anchor' => 'anchor', 'tag' => null, 'rest' => 'value'],
        ];

        yield 'text with a tag' => [
            '!!int 42',
            ['anchor' => null, 'tag' => '!!int', 'rest' => '42'],
        ];

        yield 'text with a verbose tag' => [
            '!<tag:yaml.org,2002:str> value',
            ['anchor' => null, 'tag' => '!<tag:yaml.org,2002:str>', 'rest' => 'value'],
        ];

        yield 'text with anchor and tag' => [
            '&defaults !!str value',
            ['anchor' => 'defaults', 'tag' => '!!str', 'rest' => 'value'],
        ];
    }

    public function testExtract_AnchorWithoutAName_ThrowsParseException(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('An anchor must have a name');

        NodeHeader::extract('& value', 7);
    }

    public function testInherit_OuterHeaderIsNull_ReturnsTheSameHeader(): void
    {
        $header = NodeHeader::extract('&anchor value');

        self::assertSame($header, $header->inherit(null));
    }

    public function testInherit_InnerHeaderDefinesAnchorAndTag_KeepsThem(): void
    {
        $exporter = new NodeHeaderExporter;
        $inner = NodeHeader::extract('&inner !!int 42');
        $outer = NodeHeader::extract('&outer !!str text');

        $header = $inner->inherit($outer);

        self::assertSame(
            ['anchor' => 'inner', 'tag' => '!!int', 'rest' => '42'],
            $exporter->export($header),
        );
    }

    public function testInherit_InnerHeaderIsMissingProperties_TakesThemFromOuter(): void
    {
        $exporter = new NodeHeaderExporter;
        $inner = NodeHeader::extract('value');
        $outer = NodeHeader::extract('&outer !!str text');

        $header = $inner->inherit($outer);

        self::assertSame(
            ['anchor' => 'outer', 'tag' => '!!str', 'rest' => 'value'],
            $exporter->export($header),
        );
    }

    public function testWithRest_NewText_ReplacesOnlyTheRemainingPart(): void
    {
        $exporter = new NodeHeaderExporter;
        $header = NodeHeader::extract('&anchor old')->withRest('new');

        self::assertSame(
            ['anchor' => 'anchor', 'tag' => null, 'rest' => 'new'],
            $exporter->export($header),
        );
    }
}
