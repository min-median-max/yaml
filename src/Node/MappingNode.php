<?php

declare(strict_types=1);

namespace Purr\Yaml\Node;

use Countable;

/**
 * An ordered collection of key/value pairs, written either in block or flow (`{a: 1}`) style.
 */
final class MappingNode extends AbstractNode implements Countable
{
    public function __construct(
        private readonly MappingEntryList $entries = new MappingEntryList,
        private readonly CollectionStyle $style = CollectionStyle::Block,
        ?string $anchor = null,
        ?Tag $tag = null,
        int $line = 0,
    ) {
        parent::__construct($anchor, $tag, $line);
    }

    public function getEntries(): MappingEntryList
    {
        return $this->entries;
    }

    /**
     * Looks up the value node of the first entry whose key is the given scalar text.
     */
    public function find(string $key): ?AbstractNode
    {
        foreach ($this->entries as $entry) {
            $entryKey = $entry->getKey();

            if ($entryKey instanceof ScalarNode && $key === $entryKey->getValue()) {
                return $entry->getValue();
            }
        }

        return null;
    }

    public function has(string $key): bool
    {
        return null !== $this->find($key);
    }

    public function getStyle(): CollectionStyle
    {
        return $this->style;
    }

    public function count(): int
    {
        return $this->entries->count();
    }

    public function accept(NodeVisitor $visitor): mixed
    {
        return $visitor->visitMapping($this);
    }
}
