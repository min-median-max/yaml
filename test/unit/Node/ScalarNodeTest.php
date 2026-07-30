<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Node\ScalarStyle;
use Purr\Yaml\Node\Tag;
use Purr\Yaml\Test\Fixture\NodeRenderer;

#[CoversClass(ScalarNode::class)]
final class ScalarNodeTest extends TestCase
{
    public function testGetValue_ScalarNode_ReturnsTheStoredText(): void
    {
        $node = new ScalarNode(value: 'hello', style: ScalarStyle::DoubleQuoted, line: 3);

        self::assertSame('hello', $node->getValue());
    }

    public function testGetStyle_ScalarNode_ReturnsTheStoredStyle(): void
    {
        $node = new ScalarNode(value: 'hello', style: ScalarStyle::DoubleQuoted, line: 3);

        self::assertSame(ScalarStyle::DoubleQuoted, $node->getStyle());
    }

    public function testAnchor_ScalarNode_ReturnsTheAnchorName(): void
    {
        $node = new ScalarNode(value: 'hello', anchor: 'a', line: 3);

        self::assertSame('a', $node->anchor());
    }

    public function testHasAnchor_ScalarNodeWithAnchor_ReturnsTrue(): void
    {
        $node = new ScalarNode(value: 'hello', anchor: 'a', line: 3);

        self::assertTrue($node->hasAnchor());
    }

    public function testFindTag_ScalarNode_ReturnsTheTag(): void
    {
        $tag = Tag::fromString('!!str');
        $node = new ScalarNode(value: 'hello', tag: $tag, line: 3);

        self::assertSame($tag, $node->findTag());
    }

    public function testGetLine_ScalarNode_ReturnsTheSourceLine(): void
    {
        $node = new ScalarNode(value: 'hello', line: 3);

        self::assertSame(3, $node->getLine());
    }

    public function testAccept_ScalarNode_RendersTheValue(): void
    {
        $node = new ScalarNode(value: 'hello', line: 3);

        self::assertSame('hello', $node->accept(new NodeRenderer));
    }

    public function testNull_EmptyPlainScalar_ReturnsExpectedDefaults(): void
    {
        $node = ScalarNode::null(line: 5, anchor: 'empty', tag: Tag::fromString('!!null'));

        self::assertSame('', $node->getValue());
        self::assertSame(ScalarStyle::Plain, $node->getStyle());
        self::assertSame('empty', $node->anchor());
        self::assertSame('~', $node->accept(new NodeRenderer));
    }
}
