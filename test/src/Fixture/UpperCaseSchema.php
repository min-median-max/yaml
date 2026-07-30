<?php
declare(strict_types=1);

namespace Purr\Yaml\Test\Fixture;

use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Schema\CoreSchema;
use Purr\Yaml\Schema\SchemaInterface;

/**
 * Core schema that additionally upper cases every resolved string.
 */
class UpperCaseSchema implements SchemaInterface
{
    private readonly CoreSchema $core;

    public function __construct()
    {
        $this->core = new CoreSchema;
    }

    public function resolve(ScalarNode $node): mixed
    {
        $value = $this->core->resolve($node);

        return is_string($value) ? strtoupper($value) : $value;
    }
}
