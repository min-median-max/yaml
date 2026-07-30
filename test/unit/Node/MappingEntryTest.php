<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Node\MappingEntry;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Node\ScalarStyle;
use Purr\Yaml\Test\Fixture\NodeRenderer;

#[CoversClass(MappingEntry::class)]
final class MappingEntryTest extends TestCase
{
    public function testIsMerge_PlainDoubleAngleKey_ReturnsTrue(): void
    {
        $merge = new MappingEntry(
            key: new ScalarNode(value: '<<'),
            value: new ScalarNode(value: 'ignored'),
        );

        self::assertTrue($merge->isMerge());
    }

    public function testIsMerge_QuotedKey_ReturnsFalse(): void
    {
        $regular = new MappingEntry(
            key: new ScalarNode(value: 'name', style: ScalarStyle::DoubleQuoted),
            value: new ScalarNode(value: 'purrphp'),
        );

        self::assertFalse($regular->isMerge());
    }

    public function testGetKey_Entry_ReturnsTheKeyNode(): void
    {
        $merge = new MappingEntry(
            key: new ScalarNode(value: '<<'),
            value: new ScalarNode(value: 'ignored'),
        );

        self::assertSame('<<', $merge->getKey()->accept(new NodeRenderer));
    }

    public function testGetValue_Entry_ReturnsTheValueNode(): void
    {
        $merge = new MappingEntry(
            key: new ScalarNode(value: '<<'),
            value: new ScalarNode(value: 'ignored'),
        );

        self::assertSame('ignored', $merge->getValue()->accept(new NodeRenderer));
    }
}
