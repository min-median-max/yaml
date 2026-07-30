<?php

declare(strict_types=1);

namespace Purr\Yaml\Schema;

use Purr\Yaml\Node\ScalarNode;

/**
 * Turns the text of a scalar node into a native PHP value.
 */
interface SchemaInterface
{
    public function resolve(ScalarNode $node): mixed;
}
