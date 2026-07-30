<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Node\MappingEntry;
use Purr\Yaml\Node\MappingEntryList;
use Purr\Yaml\Node\ScalarNode;

#[CoversClass(MappingEntryList::class)]
final class MappingEntryListTest extends TestCase
{
    public function testToArray_Constructed_ReturnsArrayOfEntries(): void
    {
        $e1 = new MappingEntry(
            key: new ScalarNode(value: 'name'),
            value: new ScalarNode(value: 'purrphp'),
        );
        $e2 = new MappingEntry(
            key: new ScalarNode(value: 'version'),
            value: new ScalarNode(value: '1'),
        );

        $entries = new MappingEntryList($e1, $e2);

        self::assertSame([$e1, $e2], $entries->toArray());
    }

    public function testFindFirstKey_SearchSecondEntryReturns1(): void
    {
        $e1 = new MappingEntry(
            key: new ScalarNode(value: 'name'),
            value: new ScalarNode(value: 'purrphp'),
        );
        $e2 = new MappingEntry(
            key: new ScalarNode(value: 'version'),
            value: new ScalarNode(value: '1'),
        );

        $e3 = new MappingEntry(
            key: new ScalarNode(value: 'version'),
            value: new ScalarNode(value: '1'),
        );

        $entries = new MappingEntryList($e1, $e2, $e3);

        self::assertSame(1, $entries->findFirstKey($e2));
    }
}
