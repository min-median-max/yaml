<?php

declare(strict_types=1);

namespace Purr\Yaml\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Document;
use Purr\Yaml\DocumentList;
use Purr\Yaml\Parser\Parser;
use Purr\Yaml\Schema\CoreSchema;

#[CoversClass(DocumentList::class)]
final class DocumentListTest extends TestCase
{
    public function testFindFirst_SourceWithSeveralDocuments_ReturnsTheFirstDocument(): void
    {
        $yaml = <<<'YAML'
            ---
            a: 1
            ---
            b: 2
            YAML;

        $documents = (new Parser)->parse($yaml);

        self::assertSame(['a' => 1], $documents->findFirst()?->getValue());
    }

    public function testAt_SourceWithSeveralDocuments_ReturnsTheDocumentAtIndex(): void
    {
        $yaml = <<<'YAML'
            ---
            a: 1
            ---
            b: 2
            YAML;

        $documents = (new Parser)->parse($yaml);

        self::assertSame(['b' => 2], $documents->at(1)?->getValue());
        self::assertNull($documents->at(2));
    }

    public function testMap_SourceWithSeveralDocuments_ReturnsTheValueOfEachDocument(): void
    {
        $yaml = <<<'YAML'
            ---
            a: 1
            ---
            b: 2
            YAML;

        $documents = (new Parser)->parse($yaml);

        self::assertSame(
            [['a' => 1], ['b' => 2]],
            $documents->map(static fn (Document $document): mixed => $document->getValue()),
        );
    }

    public function testIsEmpty_SourceWithoutContent_ReturnsTrue(): void
    {
        $yaml = <<<'YAML'


            # nothing here

            YAML;

        $documents = (new Parser)->parse($yaml);

        self::assertTrue($documents->isEmpty());
    }

    public function testFindFirst_SourceWithoutContent_ReturnsNull(): void
    {
        $yaml = <<<'YAML'


            # nothing here

            YAML;

        $documents = (new Parser)->parse($yaml);

        self::assertNull($documents->findFirst());
    }

    public function testMap_SourceWithoutContent_ReturnsAnEmptyList(): void
    {
        $yaml = <<<'YAML'


            # nothing here

            YAML;

        $documents = (new Parser)->parse($yaml);

        self::assertSame([], $documents->map(static fn (Document $document): mixed => $document->getValue()));
    }

    public function testCount_SourceWithSeveralDocuments_ReturnsTheNumberOfDocuments(): void
    {
        $yaml = <<<'YAML'
            ---
            a: 1
            ---
            b: 2
            YAML;

        $documents = (new Parser)->parse($yaml);

        self::assertCount(2, $documents);
    }

    public function testIsEmpty_SourceWithSeveralDocuments_ReturnsFalse(): void
    {
        $yaml = <<<'YAML'
            ---
            a: 1
            ---
            b: 2
            YAML;

        $documents = (new Parser)->parse($yaml);

        self::assertFalse($documents->isEmpty());
    }

    public function testFindKeys_Request2ndDoc_ReturnsTargetKey(): void
    {
        $yaml = <<<'YAML'
            ---
            a: 1
            ---
            b: 2
            ---
            c: 3
            YAML;

        $documents = (new Parser)->parse($yaml);

        $doc2 = $documents->toArray()[1];

        self::assertSame([1], $documents->findKeys($doc2));
    }

    public function testToPlainArray_CoreSchemaNestedYaml_ReturnsArray(): void
    {
        $yaml = <<<'YAML'
            ---
            a: 1
            child:
              nested: 2
            ---
            b: 2
            ---
            c: 3
            YAML;

        $documents = (new Parser)->parse($yaml);

        self::assertSame([
            [
                'a' => 1,
                'child' => ['nested' => 2],
            ],
            [
                'b' => 2,
            ],
            [
                'c' => 3,
            ]], $documents->toPlainArray(new CoreSchema));
    }
}
