<?php

declare(strict_types=1);

namespace Purr\Yaml\Exception;

use RuntimeException;

use function sprintf;

final class IoException extends RuntimeException implements YamlException
{
    public static function unreadable(string $path): self
    {
        return new self(sprintf('File "%s" does not exist or is not readable.', $path));
    }

    public static function unwritable(string $path): self
    {
        return new self(sprintf('File "%s" is not writable.', $path));
    }
}
