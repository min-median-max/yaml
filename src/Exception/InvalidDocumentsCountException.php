<?php
declare(strict_types=1);

namespace Purr\Yaml\Exception;

use RuntimeException;

class InvalidDocumentsCountException extends RuntimeException implements YamlException
{
    public function __construct(private readonly int $expects, private readonly int $current)
    {
        parent::__construct('Invalid documents count. Expects: '.$expects.'. Current: '.$current);
    }

    public function getExpects(): int
    {
        return $this->expects;
    }

    public function getCurrent(): int
    {
        return $this->current;
    }
}
