<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Node\CollectionStyle;
use Purr\Yaml\Node\NodeList;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Node\SequenceNode;
use Purr\Yaml\Test\Fixture\NodeRenderer;

#[CoversClass(SequenceNode::class)]
final class SequenceNodeTest extends TestCase
{
    public function testFindAt_ExistingIndex_ReturnsTheItem(): void
    {
        $sequence = new SequenceNode(
            new NodeList(
                new ScalarNode(value: 'one'),
                new ScalarNode(value: 'two'),
            ),
            style: CollectionStyle::Flow,
            line: 2,
        );

        self::assertSame('one', $sequence->findAt(0)?->accept(new NodeRenderer));
        self::assertSame('two', $sequence->findAt(1)?->accept(new NodeRenderer));
    }

    public function testFindAt_MissingIndex_ReturnsNull(): void
    {
        $sequence = new SequenceNode(
            new NodeList(
                new ScalarNode(value: 'one'),
                new ScalarNode(value: 'two'),
            ),
            style: CollectionStyle::Flow,
            line: 2,
        );

        self::assertNull($sequence->findAt(2));
    }

    public function testGetStyle_FlowSequence_ReturnsFlow(): void
    {
        $sequence = new SequenceNode(
            new NodeList(
                new ScalarNode(value: 'one'),
                new ScalarNode(value: 'two'),
            ),
            style: CollectionStyle::Flow,
            line: 2,
        );

        self::assertSame(CollectionStyle::Flow, $sequence->getStyle());
    }

    public function testCount_Items_ReturnsTheNumberOfItems(): void
    {
        $sequence = new SequenceNode(
            new NodeList(
                new ScalarNode(value: 'one'),
                new ScalarNode(value: 'two'),
            ),
            style: CollectionStyle::Flow,
            line: 2,
        );

        self::assertCount(2, $sequence);
    }

    public function testAccept_SequenceNode_RendersTheSequence(): void
    {
        $sequence = new SequenceNode(
            new NodeList(
                new ScalarNode(value: 'one'),
                new ScalarNode(value: 'two'),
            ),
            style: CollectionStyle::Flow,
            line: 2,
        );

        self::assertSame('[one, two]', $sequence->accept(new NodeRenderer));
    }
}
