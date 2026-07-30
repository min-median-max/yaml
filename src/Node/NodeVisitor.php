<?php

declare(strict_types=1);

namespace Purr\Yaml\Node;

/**
 * @template T
 */
interface NodeVisitor
{
    /**
     * @return T
     */
    public function visitScalar(ScalarNode $node): mixed;

    /**
     * @return T
     */
    public function visitSequence(SequenceNode $node): mixed;

    /**
     * @return T
     */
    public function visitMapping(MappingNode $node): mixed;

    /**
     * @return T
     */
    public function visitAlias(AliasNode $node): mixed;
}
