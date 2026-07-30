<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Node\AliasNode;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Test\Fixture\NodeRenderer;

#[CoversClass(AliasNode::class)]
final class AliasNodeTest extends TestCase
{
    public function testGetName_Alias_ReturnsItsName(): void
    {
        $target = new ScalarNode(value: 'final');
        $alias = new AliasNode(name: 'outer', target: $target, line: 3);

        self::assertSame('outer', $alias->getName());
    }

    public function testGetTarget_Alias_ReturnsTheLinkedNode(): void
    {
        $target = new ScalarNode(value: 'final');
        $middle = new AliasNode(name: 'middle', target: $target, line: 2);
        $alias = new AliasNode(name: 'outer', target: $middle, line: 3);

        self::assertSame($middle, $alias->getTarget());
    }

    public function testResolve_ChainedAliases_ReturnsTheFinalNode(): void
    {
        $target = new ScalarNode(value: 'final');
        $middle = new AliasNode(name: 'middle', target: $target, line: 2);
        $alias = new AliasNode(name: 'outer', target: $middle, line: 3);

        self::assertSame($target, $alias->resolve());
    }

    public function testAccept_Alias_RendersTheAliasReference(): void
    {
        $target = new ScalarNode(value: 'final');
        $alias = new AliasNode(name: 'outer', target: $target, line: 3);

        self::assertSame('*outer', $alias->accept(new NodeRenderer));
    }
}
