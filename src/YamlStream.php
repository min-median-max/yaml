<?php

declare(strict_types=1);

namespace Purr\Yaml;

use Purr\Yaml\Dumper\Dumper;
use Purr\Yaml\Exception\IoException;
use Purr\Yaml\Parser\Parser;
use Purr\Yaml\Schema\CoreSchema;
use Purr\Yaml\Schema\SchemaInterface;

/**
 * Entry point of the library.
 *
 * ```php
 * $yaml = new Yaml();
 *
 * $config = $yaml->parse($source);               // native PHP values
 * $documents = $yaml->parseDocuments($source); // the document model
 * $output = $yaml->dumpDocuments($documents);  // back to YAML text
 * ```
 *
 * Pass a {@see SchemaInterface} to the constructor to change how scalars become PHP values.
 */
final readonly class YamlStream
{
    public function __construct(
        private SchemaInterface $schema = new CoreSchema,
        private Parser $parser = new Parser,
        private Dumper $dumper = new Dumper,
    ) {}

    /**
     * Parses the source into the node model, keeping styles, tags and anchors.
     */
    public function fromString(string $yaml): DocumentList
    {
        return $this->parser->parse($yaml);
    }

    public function fromFile(string $path): DocumentList
    {
        return $this->fromString($this->readFile($path));
    }

    /**
     * Parses the first document of the source into native PHP values.
     */
    public function toPlainArray(string $input): mixed
    {
        return $this
            ->fromString($input)
            ->toPlainArray($this->schema)
        ;
    }

    public function fileToPlainArray(string $path): mixed
    {
        return $this->toPlainArray($this->readFile($path));
    }

    public function valueOf(Document $document): mixed
    {
        return $document->getValue($this->schema);
    }

    public function toYaml(DocumentList $documents): string
    {
        return $this->dumper->dumpDocuments($documents);
    }

    public function toFile(string $path, DocumentList $documents): void
    {
        $contents = $this->toYaml($documents);

        if (false === @file_put_contents($path, $contents)) {
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
