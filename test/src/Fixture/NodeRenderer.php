<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Fixture;

use Purr\Yaml\Node\AliasNode;
use Purr\Yaml\Node\MappingNode;
use Purr\Yaml\Node\NodeVisitor;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Node\SequenceNode;

/**
 * Renders a node graph back into a compact one line notation.
 *
 * @implements NodeVisitor<string>
 */
final class NodeRenderer implements NodeVisitor
{
    public function visitScalar(ScalarNode $node): string
    {
        return '' === $node->getValue() ? '~' : $node->getValue();
    }

    public function visitSequence(SequenceNode $node): string
    {
        $items = [];

        foreach ($node->getItems() as $item) {
            $items[] = $item->accept($this);
        }

        return '['.implode(', ', $items).']';
    }

    public function visitMapping(MappingNode $node): string
    {
        $entries = [];

        foreach ($node->getEntries() as $entry) {
            $entries[] = $entry->getKey()->accept($this).': '.$entry->getValue()->accept($this);
        }

        return '{'.implode(', ', $entries).'}';
    }

    public function visitAlias(AliasNode $node): string
    {
        return '*'.$node->getName();
    }
}
