<?php

declare(strict_types=1);

namespace Purr\Yaml;

use function count;

use Purr\Yaml\Exception\InvalidDocumentsCountException;
use Purr\Yaml\Exception\IoException;

final readonly class YamlStreamSingle
{
    public function __construct(
        private YamlStream $stream = new YamlStream,
    ) {}

    public function fromString(string $yaml): Document
    {
        $docs = $this->stream->fromString($yaml);

        $docsCount = count($docs);

        if (1 !== $docsCount) {
            throw new InvalidDocumentsCountException(expects: 1, current: $docsCount);
        }

        return $docs->findFirst();
    }

    public function fromFile(string $path): Document
    {
        return $this->fromString($this->readFile($path));
    }

    public function toPlainArray(string $input): mixed
    {
        return $this->stream->valueOf($this->fromString($input));
    }

    public function fileToPlainArray(string $path): mixed
    {
        return $this->toPlainArray($this->readFile($path));
    }

    public function toYaml(Document $document): string
    {
        return $this->stream->toYaml(new DocumentList($document));
    }

    public function toFile(string $path, Document $document): void
    {
        if (false === @file_put_contents($path, $this->toYaml($document))) {
            throw IoException::unwritable($path);
        }
    }

    private function readFile(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            throw IoException::unreadable($path);
        }

        $contents = file_get_contents($path);

        if (false === $contents) {
            throw IoException::unreadable($path);
        }

        return $contents;
    }
}
