<?php

declare(strict_types=1);

namespace Purr\Yaml\Node;

use Purr\Collection\AbstractList;

/**
 * @template-extends AbstractList<AbstractNode>
 */
final class NodeList extends AbstractList
{
    public function __construct(AbstractNode ...$items)
    {
        parent::__construct($items);
    }

    public function findAt(int $index): ?AbstractNode
    {
        return $this->collection[$index] ?? null;
    }

    protected function isSupportedType(mixed $value): bool
    {
        return $value instanceof AbstractNode;
    }

    protected function getId(mixed $value): int|string
    {
        return spl_object_id($value);
    }
}
