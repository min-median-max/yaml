<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Node\CollectionStyle;
use Purr\Yaml\Node\MappingEntry;
use Purr\Yaml\Node\MappingEntryList;
use Purr\Yaml\Node\MappingNode;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Test\Fixture\NodeRenderer;

#[CoversClass(MappingNode::class)]
final class MappingNodeTest extends TestCase
{
    public function testFind_ScalarKey_ReturnsTheMatchingValue(): void
    {
        $mapping = new MappingNode(
            entries: new MappingEntryList(
                new MappingEntry(
                    key: new ScalarNode(value: 'name'),
                    value: new ScalarNode(value: 'purrphp'),
                ),
                new MappingEntry(
                    key: new ScalarNode(value: 'version'),
                    value: new ScalarNode(value: '1'),
                ),
            ),
            style: CollectionStyle::Flow,
            anchor: 'root',
            line: 1,
        );

        self::assertSame('purrphp', $mapping->find('name')?->accept(new NodeRenderer));
    }

    public function testFind_MissingKey_ReturnsNull(): void
    {
        $mapping = new MappingNode(
            entries: new MappingEntryList(
                new MappingEntry(
                    key: new ScalarNode(value: 'name'),
                    value: new ScalarNode(value: 'purrphp'),
                ),
                new MappingEntry(
                    key: new ScalarNode(value: 'version'),
                    value: new ScalarNode(value: '1'),
                ),
            ),
            style: CollectionStyle::Flow,
            anchor: 'root',
            line: 1,
        );
        self::assertNull($mapping->find('missing'));
    }

    public function testHas_ExistingKey_ReturnsTrue(): void
    {
        $mapping = new MappingNode(
            entries: new MappingEntryList(
                new MappingEntry(
                    key: new ScalarNode(value: 'name'),
                    value: new ScalarNode(value: 'purrphp'),
                ),
                new MappingEntry(
                    key: new ScalarNode(value: 'version'),
                    value: new ScalarNode(value: '1'),
                ),
            ),
            style: CollectionStyle::Flow,
            anchor: 'root',
            line: 1,
        );
        self::assertTrue($mapping->has('name'));
    }

    public function testHas_MissingKey_ReturnsFalse(): void
    {
        $mapping = new MappingNode(
            entries: new MappingEntryList(
                new MappingEntry(
                    key: new ScalarNode(value: 'name'),
                    value: new ScalarNode(value: 'purrphp'),
                ),
                new MappingEntry(
                    key: new ScalarNode(value: 'version'),
                    value: new ScalarNode(value: '1'),
                ),
            ),
            style: CollectionStyle::Flow,
            anchor: 'root',
            line: 1,
        );
        self::assertFalse($mapping->has('missing'));
    }

    public function testGetStyle_FlowMapping_ReturnsFlow(): void
    {
        $mapping = new MappingNode(
            entries: new MappingEntryList(
                new MappingEntry(
                    key: new ScalarNode(value: 'name'),
                    value: new ScalarNode(value: 'purrphp'),
                ),
                new MappingEntry(
                    key: new ScalarNode(value: 'version'),
                    value: new ScalarNode(value: '1'),
                ),
            ),
            style: CollectionStyle::Flow,
            anchor: 'root',
            line: 1,
        );
        self::assertSame(CollectionStyle::Flow, $mapping->getStyle());
    }

    public function testCount_Entries_ReturnsTheNumberOfEntries(): void
    {
        $mapping = new MappingNode(
            entries: new MappingEntryList(
                new MappingEntry(
                    key: new ScalarNode(value: 'name'),
                    value: new ScalarNode(value: 'purrphp'),
                ),
                new MappingEntry(
                    key: new ScalarNode(value: 'version'),
                    value: new ScalarNode(value: '1'),
                ),
            ),
            style: CollectionStyle::Flow,
            anchor: 'root',
            line: 1,
        );
        self::assertCount(2, $mapping);
    }

    public function testAccept_MappingNode_RendersTheMapping(): void
    {
        $mapping = new MappingNode(
            entries: new MappingEntryList(
                new MappingEntry(
                    key: new ScalarNode(value: 'name'),
                    value: new ScalarNode(value: 'purrphp'),
                ),
                new MappingEntry(
                    key: new ScalarNode(value: 'version'),
                    value: new ScalarNode(value: '1'),
                ),
            ),
            style: CollectionStyle::Flow,
            anchor: 'root',
            line: 1,
        );
        self::assertSame('{name: purrphp, version: 1}', $mapping->accept(new NodeRenderer));
    }
}
