<?php

declare(strict_types=1);

namespace Purr\Yaml\Exception;

use RuntimeException;

use function sprintf;

final class ReferenceException extends RuntimeException implements YamlException
{
    public static function undefinedAnchor(string $name, int $line): self
    {
        return new self(sprintf('Reference "*%s" points to an undefined anchor at line %d.', $name, $line));
    }

    public static function notMergeable(string $type, int $line): self
    {
        return new self(sprintf('Merge key "<<" expects a mapping or a sequence of mappings, got %s at line %d.', $type, $line));
    }
}
