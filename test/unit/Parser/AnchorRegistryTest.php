<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Exception\ReferenceException;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Parser\AnchorRegistry;

#[CoversClass(AnchorRegistry::class)]
final class AnchorRegistryTest extends TestCase
{
    public function testRegister_AnchoredNode_StoresItInTheRegistry(): void
    {
        $registry = new AnchorRegistry;
        $node = new ScalarNode(value: '42', anchor: 'value', line: 1);

        $registry->register('value', $node);

        $this->expectNotToPerformAssertions();
    }

    public function testHas_RegisteredAnchor_ReturnsTrue(): void
    {
        $registry = new AnchorRegistry;
        $node = new ScalarNode(value: '42', anchor: 'value', line: 1);

        $registry->register('value', $node);

        self::assertTrue($registry->has('value'));
    }

    public function testGet_RegisteredAnchor_ReturnsTheNode(): void
    {
        $registry = new AnchorRegistry;
        $node = new ScalarNode(value: '42', anchor: 'value', line: 1);

        $registry->register('value', $node);

        self::assertSame($node, $registry->get('value', 1));
    }

    public function testAll_RegisteredAnchors_ReturnsThemByName(): void
    {
        $registry = new AnchorRegistry;
        $node = new ScalarNode(value: '42', anchor: 'value', line: 1);

        $registry->register('value', $node);

        self::assertSame(['value' => $node], $registry->all());
    }

    public function testKeep_RegistersNodesThatCarryAnAnchor(): void
    {
        $registry = new AnchorRegistry;
        $node = new ScalarNode(value: '42', anchor: 'value', line: 1);

        self::assertSame($node, $registry->keep($node));
    }

    public function testHas_AnchorRegisteredViaKeep_ReturnsTrue(): void
    {
        $registry = new AnchorRegistry;
        $node = new ScalarNode(value: '42', anchor: 'value', line: 1);

        $registry->keep($node);

        self::assertTrue($registry->has('value'));
    }

    public function testKeep_NodeWithoutAnchor_IsReturnedUnchanged(): void
    {
        $registry = new AnchorRegistry;
        $node = new ScalarNode(value: 'plain', line: 1);

        self::assertSame($node, $registry->keep($node));
    }

    public function testHas_NodeWithoutAnchor_ReturnsFalse(): void
    {
        $registry = new AnchorRegistry;
        $node = new ScalarNode(value: 'plain', line: 1);

        $registry->keep($node);

        self::assertFalse($registry->has('plain'));
    }

    public function testAll_NoRegisteredAnchors_ReturnsAnEmptyList(): void
    {
        $registry = new AnchorRegistry;
        $node = new ScalarNode(value: 'plain', line: 1);

        $registry->keep($node);

        self::assertSame([], $registry->all());
    }

    public function testGet_UndefinedAnchor_ThrowsReferenceException(): void
    {
        $registry = new AnchorRegistry;
        $this->expectException(ReferenceException::class);
        $this->expectExceptionMessage('*missing');

        $registry->get('missing', 3);
    }
}
