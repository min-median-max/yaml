<?php

declare(strict_types=1);

namespace Purr\Yaml\Exception;

use RuntimeException;

use function sprintf;

final class ParseException extends RuntimeException implements YamlException
{
    public function __construct(
        string $message,
        private readonly int $sourceLine = 0,
        private readonly ?string $snippet = null,
    ) {
        parent::__construct(self::format($message, $sourceLine, $snippet));
    }

    /**
     * Line of the YAML source the error was found on.
     */
    public function line(): int
    {
        return $this->sourceLine;
    }

    public function snippet(): ?string
    {
        return $this->snippet;
    }

    private static function format(string $message, int $line, ?string $snippet): string
    {
        $message = rtrim($message, '.');

        if (null !== $snippet && '' !== $snippet) {
            $message .= sprintf(' (near "%s")', trim($snippet));
        }

        if ($line > 0) {
            $message .= sprintf(' at line %d', $line);
        }

        return $message.'.';
    }
}
