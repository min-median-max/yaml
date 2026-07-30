<?php

declare(strict_types=1);

namespace Purr\Yaml\Node;

/**
 * A reference (`*name`) to a node previously anchored with `&name`.
 *
 * The alias keeps a link to the very node it points at, so the model stays a graph
 * instead of a copy of the anchored value.
 */
final class AliasNode extends AbstractNode
{
    public function __construct(
        private readonly string $name,
        private readonly AbstractNode $target,
        int $line = 0,
    ) {
        parent::__construct(null, null, $line);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTarget(): AbstractNode
    {
        return $this->target;
    }

    /**
     * Follows chained aliases down to the first non-alias node.
     */
    public function resolve(): AbstractNode
    {
        $node = $this->target;

        while ($node instanceof self) {
            $node = $node->getTarget();
        }

        return $node;
    }

    public function accept(NodeVisitor $visitor): mixed
    {
        return $visitor->visitAlias($this);
    }
}
