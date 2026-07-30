<?php

declare(strict_types=1);

namespace Purr\Yaml\Node;

use Purr\Collection\AbstractList;

/**
 * @template-extends AbstractList<MappingEntry>
 */
final class MappingEntryList extends AbstractList
{
    public function __construct(MappingEntry ...$items)
    {
        parent::__construct($items);
    }

    protected function isSupportedType(mixed $value): bool
    {
        return $value instanceof MappingEntry;
    }

    protected function getId(mixed $value): int|string
    {
        return spl_object_id($value);
    }
}
