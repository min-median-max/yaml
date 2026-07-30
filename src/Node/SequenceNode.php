<?php

declare(strict_types=1);

namespace Purr\Yaml\Node;

use Countable;

/**
 * An ordered collection of nodes, written either in block (`- item`) or flow (`[item]`) style.
 */
final class SequenceNode extends AbstractNode implements Countable
{
    public function __construct(
        private readonly NodeList $items = new NodeList,
        private readonly CollectionStyle $style = CollectionStyle::Block,
        ?string $anchor = null,
        ?Tag $tag = null,
        int $line = 0,
    ) {
        parent::__construct($anchor, $tag, $line);
    }

    public function getItems(): NodeList
    {
        return $this->items;
    }

    public function findAt(int $index): ?AbstractNode
    {
        return $this->items->findAt($index);
    }

    public function getStyle(): CollectionStyle
    {
        return $this->style;
    }

    public function count(): int
    {
        return $this->items->count();
    }

    public function accept(NodeVisitor $visitor): mixed
    {
        return $visitor->visitSequence($this);
    }
}
