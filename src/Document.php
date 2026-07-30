<?php

declare(strict_types=1);

namespace Purr\Yaml;

use Purr\Yaml\Node\AbstractNode;
use Purr\Yaml\Schema\SchemaInterface;
use Purr\Yaml\Value\ValueBuilder;

/**
 * One YAML document: its root node plus the anchors declared inside it.
 */
final class Document
{
    /**
     * @param array<string, AbstractNode> $anchors
     */
    public function __construct(
        private readonly AbstractNode $root,
        private readonly array $anchors = [],
        private readonly int $line = 1,
    ) {}

    public function getRoot(): AbstractNode
    {
        return $this->root;
    }

    /**
     * @return array<string, AbstractNode>
     */
    public function getAnchors(): array
    {
        return $this->anchors;
    }

    public function findAnchor(string $name): ?AbstractNode
    {
        return $this->anchors[$name] ?? null;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * The document as native PHP values, with aliases and merge keys expanded.
     */
    public function getValue(?SchemaInterface $schema = null): mixed
    {
        $builder = null === $schema ? new ValueBuilder : new ValueBuilder($schema);

        return $builder->build($this->root);
    }
}
