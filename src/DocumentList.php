<?php

declare(strict_types=1);

namespace Purr\Yaml;

use Purr\Collection\AbstractList;
use Purr\Yaml\Schema\SchemaInterface;

/**
 * @template-extends AbstractList<Document>
 */
final class DocumentList extends AbstractList
{
    public function __construct(Document ...$items)
    {
        parent::__construct($items);
    }

    public function at(int $index): ?Document
    {
        return $this->toArray()[$index] ?? null;
    }

    public function toPlainArray(SchemaInterface $schema): mixed
    {
        return $this->map(fn (Document $document): mixed => $document->getValue($schema));
    }

    protected function isSupportedType(mixed $value): bool
    {
        return $value instanceof Document;
    }

    protected function getId(mixed $value): int|string
    {
        return spl_object_id($value);
    }
}
