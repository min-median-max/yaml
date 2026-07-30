<?php

declare(strict_types=1);

namespace Purr\Yaml\Value;

use function array_key_exists;
use function is_int;
use function is_string;

use Purr\Yaml\Exception\ReferenceException;
use Purr\Yaml\Exception\ValueException;
use Purr\Yaml\Node\AbstractNode;
use Purr\Yaml\Node\AliasNode;
use Purr\Yaml\Node\MappingNode;
use Purr\Yaml\Node\NodeVisitor;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Node\SequenceNode;
use Purr\Yaml\Schema\CoreSchema;
use Purr\Yaml\Schema\SchemaInterface;

/**
 * Converts a node graph into plain PHP values, expanding aliases and merge keys.
 *
 * @implements NodeVisitor<mixed>
 */
final class ValueBuilder implements NodeVisitor
{
    public function __construct(private readonly SchemaInterface $schema = new CoreSchema) {}

    public function build(AbstractNode $node): mixed
    {
        return $node->accept($this);
    }

    public function visitScalar(ScalarNode $node): mixed
    {
        return $this->schema->resolve($node);
    }

    public function visitSequence(SequenceNode $node): mixed
    {
        $items = [];

        foreach ($node->getItems() as $item) {
            $items[] = $item->accept($this);
        }

        return $items;
    }

    public function visitMapping(MappingNode $node): mixed
    {
        $result = [];

        foreach ($node->getEntries() as $entry) {
            if ($entry->isMerge()) {
                foreach ($this->mergeSources($entry->getValue()) as $source) {
                    foreach ($source as $key => $value) {
                        if (!array_key_exists($key, $result)) {
                            $result[$key] = $value;
                        }
                    }
                }

                continue;
            }

            $result[$this->keyOf($entry->getKey())] = $entry->getValue()->accept($this);
        }

        return $result;
    }

    public function visitAlias(AliasNode $node): mixed
    {
        return $node->getTarget()->accept($this);
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    private function mergeSources(AbstractNode $node): array
    {
        $resolved = $node instanceof AliasNode ? $node->resolve() : $node;

        if ($resolved instanceof MappingNode) {
            /** @var array<array-key, mixed> $value */
            $value = $resolved->accept($this);

            return [$value];
        }

        if ($resolved instanceof SequenceNode) {
            $sources = [];

            foreach ($resolved->getItems() as $item) {
                $sources = [...$sources, ...$this->mergeSources($item)];
            }

            return $sources;
        }

        throw ReferenceException::notMergeable($this->describe($resolved), $node->getLine());
    }

    private function keyOf(AbstractNode $node): int|string
    {
        $value = $node->accept($this);

        if (is_int($value) || is_string($value)) {
            return $value;
        }

        $resolved = $node instanceof AliasNode ? $node->resolve() : $node;

        if ($resolved instanceof ScalarNode) {
            return $resolved->getValue();
        }

        throw ValueException::unsupportedKey($this->describe($resolved), $node->getLine());
    }

    private function describe(AbstractNode $node): string
    {
        return match (true) {
            $node instanceof MappingNode => 'mapping',
            $node instanceof SequenceNode => 'sequence',
            $node instanceof AliasNode => 'alias',
            default => 'scalar',
        };
    }
}
