<?php

declare(strict_types=1);

namespace Purr\Yaml\Node;

/**
 * A single key/value pair of a mapping. Both sides are full nodes on their own.
 */
final class MappingEntry
{
    public function __construct(
        private readonly AbstractNode $key,
        private readonly AbstractNode $value,
    ) {}

    public function getKey(): AbstractNode
    {
        return $this->key;
    }

    public function getValue(): AbstractNode
    {
        return $this->value;
    }

    /**
     * Whether the entry is a merge key (`<<: *anchor`) that injects another mapping.
     */
    public function isMerge(): bool
    {
        $key = $this->key;

        return $key instanceof ScalarNode
            && '<<' === $key->getValue()
            && ScalarStyle::Plain === $key->getStyle();
    }
}
