<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Node\Tag;
use Purr\Yaml\Node\TagType;

#[CoversClass(Tag::class)]
final class TagTest extends TestCase
{
    public function testEquals_TagsWrittenTheSameWay_ReturnsTrue(): void
    {
        self::assertTrue(Tag::fromString('!!int')->equals(Tag::fromString('!!int')));
    }

    public function testEquals_DifferentTags_ReturnsFalse(): void
    {
        self::assertFalse(Tag::fromString('!!int')->equals(Tag::fromString('!!str')));
    }

    public function testFindName_VerboseFormOfAStandardTag_ReturnsItsShortName(): void
    {
        self::assertSame('str', Tag::fromString('!<tag:yaml.org,2002:str>')->findName());
    }

    public function testFindName_ShortStandardTag_ReturnsItsShortName(): void
    {
        self::assertSame('seq', Tag::fromString('!!seq')->findName());
    }

    public function testFindName_ApplicationTag_ReturnsNull(): void
    {
        self::assertNull(Tag::fromString('!point')->findName());
    }

    public function testToString_Tag_ReturnsTheTagText(): void
    {
        self::assertSame('!!seq', (string) Tag::fromString('!!seq'));
    }

    #[DataProvider('values')]
    public function testGetValue_Tag_ReturnsTheOriginalTagText(string $tag): void
    {
        self::assertSame($tag, Tag::fromString($tag)->getValue());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function values(): iterable
    {
        yield 'short standard tag' => ['!!int'];
        yield 'application tag' => ['!custom'];
    }

    #[DataProvider('standardTags')]
    public function testIsStandard_StandardTag_ReturnsTrue(string $tag): void
    {
        self::assertTrue(Tag::fromString($tag)->isStandard());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function standardTags(): iterable
    {
        yield 'short form' => ['!!str'];
        yield 'verbose form' => ['!<tag:yaml.org,2002:int>'];
    }

    public function testIsStandard_ApplicationTag_ReturnsFalse(): void
    {
        self::assertFalse(Tag::fromString('!point')->isStandard());
    }

    public function testIs_MatchingTagType_ReturnsTrue(): void
    {
        self::assertTrue(Tag::fromString('!!bool')->is(TagType::Bool));
    }

    public function testFindType_StandardTag_ReturnsTheMatchingType(): void
    {
        self::assertSame(TagType::Int, Tag::fromString('!!int')->findType());
    }

    public function testFindType_ApplicationTag_ReturnsNull(): void
    {
        self::assertNull(Tag::fromString('!custom')->findType());
    }
}
