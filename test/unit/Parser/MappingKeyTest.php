<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Parser\MappingKey;
use Purr\Yaml\Parser\TextHelper;

#[CoversClass(MappingKey::class)]
final class MappingKeyTest extends TestCase
{
    public function testGetKey_SimpleMappingEntry_ReturnsKey(): void
    {
        $textHelper = new TextHelper;

        $mappingKey = MappingKey::fromText($textHelper, 'key: value');

        self::assertEquals('key', $mappingKey->getKey());
    }

    public function testGetValue_SimpleMappingEntry_ReturnsValue(): void
    {
        $textHelper = new TextHelper;

        $mappingKey = MappingKey::fromText($textHelper, 'key: value');

        self::assertEquals('value', $mappingKey->getValue());
    }

    public function testFromText_SimpleMappingEntry_SplitsKeyAndValue(): void
    {
        $textHelper = new TextHelper;

        $mappingKey = MappingKey::fromText($textHelper, 'key: value');

        self::assertEquals(new MappingKey('key', 'value'), $mappingKey);
    }

    public function testFromText_KeyWithQuotedColon_IgnoresTheColonInsideQuotes(): void
    {
        $textHelper = new TextHelper;

        $mappingKey = MappingKey::fromText($textHelper, '"with: colon": value');

        self::assertEquals(new MappingKey('"with: colon"', 'value'), $mappingKey);
    }

    public function testFromText_KeyWithFlowCollection_IgnoresColonsInsideBrackets(): void
    {
        $textHelper = new TextHelper;

        $mappingKey = MappingKey::fromText($textHelper, 'list: [a: 1, b: 2]');

        self::assertEquals(new MappingKey('list', '[a: 1, b: 2]'), $mappingKey);
    }

    public function testFromText_KeyWithFlowSequenceInKey_IgnoresColonInsideBrackets(): void
    {
        $textHelper = new TextHelper;

        $mappingKey = MappingKey::fromText($textHelper, '[a: 1]: value');

        self::assertEquals(new MappingKey('[a: 1]', 'value'), $mappingKey);
    }

    public function testFromText_KeyWithFlowMappingInKey_IgnoresColonInsideBraces(): void
    {
        $textHelper = new TextHelper;

        $mappingKey = MappingKey::fromText($textHelper, '{a: 1}: value');

        self::assertEquals(new MappingKey('{a: 1}', 'value'), $mappingKey);
    }

    public function testFromText_KeyWithFlowNestedCollection_IgnoresColonsInsideBrackets(): void
    {
        $textHelper = new TextHelper;

        $mappingKey = MappingKey::fromText($textHelper, 'list: [a: 1, b: [c: 2, d: [e:3, f:4]]]');

        self::assertEquals(new MappingKey('list', '[a: 1, b: [c: 2, d: [e:3, f:4]]]'), $mappingKey);
    }

    public function testFromText_KeyWithFlowMapping_IgnoresColonsInsideBraces(): void
    {
        $textHelper = new TextHelper;

        $mappingKey = MappingKey::fromText($textHelper, 'map: {a: 1, b: 2}');

        self::assertEquals(new MappingKey('map', '{a: 1, b: 2}'), $mappingKey);
    }

    public function testFromText_KeyWithFlowNestedMapping_IgnoresColonsInsideBraces(): void
    {
        $textHelper = new TextHelper;

        $mappingKey = MappingKey::fromText($textHelper, 'map: {a: 1, b: {c: 2, d: 3}}');

        self::assertEquals(new MappingKey('map', '{a: 1, b: {c: 2, d: 3}}'), $mappingKey);
    }

    public function testFromText_UnterminatedQuotedKey_ReturnsNull(): void
    {
        $textHelper = new TextHelper;

        self::assertNull(MappingKey::fromText($textHelper, '"open key: value'));
    }

    public function testFromText_TextWithoutMappingSeparator_ReturnsNull(): void
    {
        $textHelper = new TextHelper;

        self::assertNull(MappingKey::fromText($textHelper, 'just text'));
    }
}
