<?php

declare(strict_types=1);

namespace Purr\Yaml\Parser;

use Purr\Yaml\Exception\ReferenceException;
use Purr\Yaml\Node\AbstractNode;

/**
 * Anchors declared within a single document, in declaration order.
 */
final class AnchorRegistry
{
    /**
     * @var array<string, AbstractNode>
     */
    private array $anchors = [];

    public function register(string $name, AbstractNode $node): void
    {
        $this->anchors[$name] = $node;
    }

    /**
     * Registers the node when it carries an anchor and returns it unchanged.
     */
    public function keep(AbstractNode $node): AbstractNode
    {
        $anchor = $node->anchor();

        if (null !== $anchor) {
            $this->anchors[$anchor] = $node;
        }

        return $node;
    }

    public function has(string $name): bool
    {
        return isset($this->anchors[$name]);
    }

    public function get(string $name, int $line = 0): AbstractNode
    {
        return $this->anchors[$name] ?? throw ReferenceException::undefinedAnchor($name, $line);
    }

    /**
     * @return array<string, AbstractNode>
     */
    public function all(): array
    {
        return $this->anchors;
    }
}
