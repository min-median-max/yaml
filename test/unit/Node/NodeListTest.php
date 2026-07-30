<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Node\NodeList;
use Purr\Yaml\Node\ScalarNode;

#[CoversClass(NodeList::class)]
final class NodeListTest extends TestCase
{
    public function testToArray_Constructed_ReturnsArrayOfNodes(): void
    {
        $n1 = new ScalarNode(value: 'one');
        $n2 = new ScalarNode(value: 'two');

        $items = new NodeList($n1, $n2);

        self::assertSame([$n1, $n2], $items->toArray());
    }

    public function testFindAt_ExistingIndex_ReturnsTheNode(): void
    {
        $n1 = new ScalarNode(value: 'one');
        $n2 = new ScalarNode(value: 'two');

        $items = new NodeList($n1, $n2);

        self::assertSame($n1, $items->findAt(0));
        self::assertSame($n2, $items->findAt(1));
    }

    public function testFindAt_MissingIndex_ReturnsNull(): void
    {
        $items = new NodeList(
            new ScalarNode(value: 'one'),
            new ScalarNode(value: 'two'),
        );

        self::assertNull($items->findAt(2));
    }

    public function testFindFirstKey_SearchSecondItemReturns1(): void
    {
        $n1 = new ScalarNode(value: 'one');
        $n2 = new ScalarNode(value: 'two');
        $n3 = new ScalarNode(value: 'two');

        $items = new NodeList($n1, $n2, $n3);

        self::assertSame(1, $items->findFirstKey($n2));
    }
}
