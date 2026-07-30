<?php

declare(strict_types=1);

namespace Purr\Yaml\Node;

/**
 * Base class of every YAML node: a value together with its anchor, tag and source position.
 */
abstract class AbstractNode
{
    public function __construct(
        private readonly ?string $anchor = null,
        private readonly ?Tag $tag = null,
        private readonly int $line = 0,
    ) {}

    final public function anchor(): ?string
    {
        return $this->anchor;
    }

    final public function hasAnchor(): bool
    {
        return null !== $this->anchor;
    }

    final public function findTag(): ?Tag
    {
        return $this->tag;
    }

    final public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @template T
     *
     * @param NodeVisitor<T> $visitor
     *
     * @return T
     */
    abstract public function accept(NodeVisitor $visitor): mixed;
}
