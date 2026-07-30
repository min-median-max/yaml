<?php

declare(strict_types=1);

namespace Purr\Yaml\Exception;

use RuntimeException;

use function sprintf;

final class ValueException extends RuntimeException implements YamlException
{
    public static function unsupportedKey(string $type, int $line): self
    {
        return new self(sprintf('A %s cannot be used as a mapping key at line %d.', $type, $line));
    }
}
