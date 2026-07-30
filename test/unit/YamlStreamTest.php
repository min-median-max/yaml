<?php

declare(strict_types=1);

namespace Purr\Yaml\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Document;
use Purr\Yaml\DocumentList;
use Purr\Yaml\Exception\IoException;
use Purr\Yaml\Exception\ParseException;
use Purr\Yaml\Node\AliasNode;
use Purr\Yaml\Node\CollectionStyle;
use Purr\Yaml\Node\MappingEntry;
use Purr\Yaml\Node\MappingEntryList;
use Purr\Yaml\Node\MappingNode;
use Purr\Yaml\Node\NodeList;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Node\ScalarStyle;
use Purr\Yaml\Node\SequenceNode;
use Purr\Yaml\YamlStream;

#[CoversClass(YamlStream::class)]
final class YamlStreamTest extends TestCase
{
    public function testToPlainArray_SourceWithSeveralDocuments_ReturnsTheValueOfEachOne(): void
    {
        $yamlStream = new YamlStream;
        $source = <<<'YAML'
            ---
            name: first
            ---
            name: second
            ...
            YAML;

        self::assertSame([['name' => 'first'], ['name' => 'second']], $yamlStream->toPlainArray($source));
    }

    public function testFromString_SourceWithSeveralDocuments_ReturnsAllDocuments(): void
    {
        $yamlStream = new YamlStream;
        $source = <<<'YAML'
            ---
            name: first
            ---
            name: second
            ...
            YAML;

        self::assertEquals(
            new DocumentList(
                self::nameDocument('first', docLine: 1, mappingLine: 2),
                self::nameDocument('second', docLine: 3, mappingLine: 4),
            ),
            $yamlStream->fromString($source),
        );
    }

    public function testFromString_SourceWithSeveralDocuments_ReturnsTheFirstDocumentOnly(): void
    {
        $yamlStream = new YamlStream;
        $yaml = <<<'YAML'
            ---
            name: first
            ---
            name: second
            YAML;

        $document = $yamlStream->fromString($yaml)->findFirst();
        self::assertNotNull($document);
        self::assertEquals(
            self::nameDocument('first', docLine: 1, mappingLine: 2)->getRoot(),
            $document->getRoot(),
        );
    }

    public function testToPlainArray_ValueWrittenOnTheDocumentStartLine_ReturnsThatValue(): void
    {
        $yamlStream = new YamlStream;
        $yaml = <<<'YAML'
            --- inline
            --- other
            YAML;

        self::assertSame(['inline', 'other'], $yamlStream->toPlainArray($yaml));
    }

    public function testFromString_ValueWrittenOnTheDocumentStartLine_ReturnsThoseScalars(): void
    {
        $yamlStream = new YamlStream;
        $yaml = <<<'YAML'
            --- inline
            --- other
            YAML;

        self::assertEquals(
            new DocumentList(
                new Document(
                    root: new ScalarNode(value: 'inline', line: 1),
                    line: 1,
                ),
                new Document(
                    root: new ScalarNode(value: 'other', line: 2),
                    line: 2,
                ),
            ),
            $yamlStream->fromString($yaml),
        );
    }

    public function testFromString_SourceWithYaml12Directive_ParsesTheDocument(): void
    {
        $yamlStream = new YamlStream;
        $yaml = <<<'YAML'
            %YAML 1.2
            ---
            key: value
            YAML;

        $document = $yamlStream->fromString($yaml)->findFirst();
        self::assertNotNull($document);
        self::assertEquals(
            new Document(
                root: new MappingNode(
                    entries: new MappingEntryList(
                        new MappingEntry(
                            key: new ScalarNode(value: 'key', line: 3),
                            value: new ScalarNode(value: 'value', line: 3),
                        ),
                    ),
                    style: CollectionStyle::Block,
                    line: 3,
                ),
                line: 2,
            ),
            $document,
        );
    }

    public function testFromString_SourceWithYaml11Directive_ThrowsParseException(): void
    {
        $yamlStream = new YamlStream;
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unsupported YAML version "1.1"');

        $yamlStream->fromString("%YAML 1.1\n---\nkey: value\n");
    }

    public function testFromFile_ReadableFile_ReturnsItsDocuments(): void
    {
        $yamlStream = new YamlStream;
        $path = __DIR__.'/../src/Fixture/some.yaml';

        self::assertEquals(
            self::someYamlDocumentList(),
            $yamlStream->fromFile($path),
        );
    }

    public function testFileToPlainArray_ReadableFile_ReturnsItsValue(): void
    {
        $yamlStream = new YamlStream;
        $path = __DIR__.'/../src/Fixture/some.yaml';
        $contents = file_get_contents($path);
        self::assertNotFalse($contents);

        self::assertSame($yamlStream->toPlainArray($contents), $yamlStream->fileToPlainArray($path));
    }

    public function testFileToPlainArray_MissingFile_ThrowsIoException(): void
    {
        $yamlStream = new YamlStream;
        $this->expectException(IoException::class);

        $yamlStream->fileToPlainArray(__DIR__.'/does-not-exist.yaml');
    }

    public function testValueOf_SimpleDocument_ReturnsValueWithDefaultSchema(): void
    {
        $yamlStream = new YamlStream;

        $document = new Document(
            root: new MappingNode(
                entries: new MappingEntryList(
                    new MappingEntry(
                        key: new ScalarNode(value: 'key', line: 1),
                        value: new ScalarNode(value: 'value', line: 1),
                    ),
                ),
                style: CollectionStyle::Block,
                line: 1,
            ),
            line: 1,
        );

        self::assertEquals([
            'key' => 'value',
        ], $yamlStream->valueOf($document));
    }

    public function testToFile_UnwritablePath_ThrowsIoException(): void
    {
        $yamlStream = new YamlStream;
        $documents = $yamlStream->fromString("key: value\n");
        $path = sys_get_temp_dir().'/purrphp-yaml-readonly-'.uniqid('', true).'.yaml';
        file_put_contents($path, "existing\n");
        chmod($path, 0o444);

        try {
            $this->expectException(IoException::class);
            $this->expectExceptionMessage('not writable');

            $yamlStream->toFile($path, $documents);
        } finally {
            chmod($path, 0o644);
            @unlink($path);
        }
    }

    public function testFromString_SourceWithWindowsLineEndings_ReadsItLikeUnixLines(): void
    {
        $yamlStream = new YamlStream;
        $yaml = "a: 1\r\nb: 2\r\n";

        $document = $yamlStream->fromString($yaml)->findFirst();
        self::assertNotNull($document);
        self::assertEquals(
            new MappingNode(
                entries: new MappingEntryList(
                    new MappingEntry(
                        key: new ScalarNode(value: 'a', line: 1),
                        value: new ScalarNode(value: '1', line: 1),
                    ),
                    new MappingEntry(
                        key: new ScalarNode(value: 'b', line: 2),
                        value: new ScalarNode(value: '2', line: 2),
                    ),
                ),
                style: CollectionStyle::Block,
                line: 1,
            ),
            $document->getRoot(),
        );
    }

    public function testToYaml_DocumentList_RoundTripsItsDocuments(): void
    {
        $yamlStream = new YamlStream;
        $source = <<<'YAML'
            ---
            name: first
            ---
            name: second
            YAML;

        $documents = $yamlStream->fromString($source);
        $roundTripped = $yamlStream->fromString($yamlStream->toYaml($documents));

        self::assertCount(2, $roundTripped);
        self::assertEquals(
            self::nameDocument('first', docLine: 1, mappingLine: 1)->getRoot(),
            $roundTripped->at(0)?->getRoot(),
        );
        self::assertEquals(
            self::nameDocument('second', docLine: 2, mappingLine: 3)->getRoot(),
            $roundTripped->at(1)?->getRoot(),
        );
    }

    public function testToFile_ValidFile_WritesSerializedDocument(): void
    {
        $yamlStream = new YamlStream;
        $documents = $yamlStream->fromString("key: value\n");
        $path = sys_get_temp_dir().'/purrphp-yaml-dump-'.uniqid('', true).'.yaml';

        try {
            $yamlStream->toFile($path, $documents);

            $roundTripped = $yamlStream->fromFile($path)->findFirst();
            self::assertNotNull($roundTripped);
            self::assertEquals(self::keyValueDocument()->getRoot(), $roundTripped->getRoot());
        } finally {
            @unlink($path);
        }
    }

    private static function keyValueDocument(): Document
    {
        return new Document(
            root: new MappingNode(
                entries: new MappingEntryList(
                    new MappingEntry(
                        key: new ScalarNode(value: 'key', line: 1),
                        value: new ScalarNode(value: 'value', line: 1),
                    ),
                ),
                style: CollectionStyle::Block,
                line: 1,
            ),
            line: 1,
        );
    }

    private static function nameDocument(string $name, int $docLine, int $mappingLine): Document
    {
        return new Document(
            root: new MappingNode(
                entries: new MappingEntryList(
                    new MappingEntry(
                        key: new ScalarNode(value: 'name', line: $mappingLine),
                        value: new ScalarNode(value: $name, line: $mappingLine),
                    ),
                ),
                style: CollectionStyle::Block,
                line: $mappingLine,
            ),
            line: $docLine,
        );
    }

    private static function someYamlDocumentList(): DocumentList
    {
        $foldersSequence = new SequenceNode(
            items: new NodeList(
                new MappingNode(
                    entries: new MappingEntryList(
                        new MappingEntry(
                            key: new ScalarNode(value: 'src', line: 2),
                            value: new ScalarNode(value: './app/**', line: 2),
                        ),
                        new MappingEntry(
                            key: new ScalarNode(value: 'dest', line: 3),
                            value: new ScalarNode(value: '/app', line: 3),
                        ),
                    ),
                    line: 2,
                ),
                new MappingNode(
                    entries: new MappingEntryList(
                        new MappingEntry(
                            key: new ScalarNode(value: 'assets', line: 4),
                            value: new ScalarNode(value: './assets/**', line: 4),
                        ),
                        new MappingEntry(
                            key: new ScalarNode(value: 'dest', line: 5),
                            value: new ScalarNode(value: '/assets', line: 5),
                        ),
                    ),
                    line: 4,
                ),
            ),
            anchor: 'folders',
            line: 2,
        );

        return new DocumentList(
            new Document(
                root: new MappingNode(
                    entries: new MappingEntryList(
                        new MappingEntry(
                            key: new ScalarNode(value: '.folders', line: 1),
                            value: $foldersSequence,
                        ),
                        new MappingEntry(
                            key: new ScalarNode(value: 'config', line: 7),
                            value: new MappingNode(
                                entries: new MappingEntryList(
                                    new MappingEntry(
                                        key: new ScalarNode(value: 'engine', line: 8),
                                        value: new ScalarNode(value: 'redis', style: ScalarStyle::DoubleQuoted, line: 8),
                                    ),
                                    new MappingEntry(
                                        key: new ScalarNode(value: 'presence', line: 9),
                                        value: new ScalarNode(value: 'true', line: 9),
                                    ),
                                    new MappingEntry(
                                        key: new ScalarNode(value: 'history_size', line: 10),
                                        value: new ScalarNode(value: '300', line: 10),
                                    ),
                                    new MappingEntry(
                                        key: new ScalarNode(value: 'allow_subscribe_for_client', line: 11),
                                        value: new ScalarNode(value: 'true', line: 11),
                                    ),
                                ),
                                line: 8,
                            ),
                        ),
                        new MappingEntry(
                            key: new ScalarNode(value: 'service', line: 13),
                            value: new MappingNode(
                                entries: new MappingEntryList(
                                    new MappingEntry(
                                        key: new ScalarNode(value: 'enabled', line: 14),
                                        value: new ScalarNode(value: 'false', line: 14),
                                    ),
                                    new MappingEntry(
                                        key: new ScalarNode(value: 'command', line: 15),
                                        value: new SequenceNode(
                                            items: new NodeList(
                                                new ScalarNode(value: '/bin/bash', line: 16),
                                                new ScalarNode(value: '-exc', line: 17),
                                                new ScalarNode(value: 'cp files', style: ScalarStyle::Literal, line: 18),
                                            ),
                                            line: 16,
                                        ),
                                    ),
                                    new MappingEntry(
                                        key: new ScalarNode(value: 'files', line: 20),
                                        value: new AliasNode(name: 'folders', target: $foldersSequence, line: 20),
                                    ),
                                ),
                                line: 14,
                            ),
                        ),
                        new MappingEntry(
                            key: new ScalarNode(value: 'affinity', line: 22),
                            value: new MappingNode(
                                entries: new MappingEntryList(
                                    new MappingEntry(
                                        key: new ScalarNode(value: 'nodeAffinity', line: 23),
                                        value: new MappingNode(
                                            entries: new MappingEntryList(
                                                new MappingEntry(
                                                    key: new ScalarNode(value: 'preferredDuringSchedulingIgnoredDuringExecution', line: 24),
                                                    value: new SequenceNode(
                                                        items: new NodeList(
                                                            new MappingNode(
                                                                entries: new MappingEntryList(
                                                                    new MappingEntry(
                                                                        key: new ScalarNode(value: 'weight', line: 25),
                                                                        value: new ScalarNode(value: '10', line: 25),
                                                                    ),
                                                                    new MappingEntry(
                                                                        key: new ScalarNode(value: 'preference', line: 26),
                                                                        value: new MappingNode(
                                                                            entries: new MappingEntryList(
                                                                                new MappingEntry(
                                                                                    key: new ScalarNode(value: 'matchExpressions', line: 27),
                                                                                    value: new SequenceNode(
                                                                                        items: new NodeList(
                                                                                            new MappingNode(
                                                                                                entries: new MappingEntryList(
                                                                                                    new MappingEntry(
                                                                                                        key: new ScalarNode(value: 'key', line: 28),
                                                                                                        value: new ScalarNode(value: 'topology.kubernetes.io/region', line: 28),
                                                                                                    ),
                                                                                                    new MappingEntry(
                                                                                                        key: new ScalarNode(value: 'operator', line: 29),
                                                                                                        value: new ScalarNode(value: 'NotIn', line: 29),
                                                                                                    ),
                                                                                                    new MappingEntry(
                                                                                                        key: new ScalarNode(value: 'values', line: 30),
                                                                                                        value: new SequenceNode(
                                                                                                            items: new NodeList(
                                                                                                                new ScalarNode(value: 'HEL', line: 31),
                                                                                                            ),
                                                                                                            line: 31,
                                                                                                        ),
                                                                                                    ),
                                                                                                ),
                                                                                                line: 28,
                                                                                            ),
                                                                                        ),
                                                                                        line: 28,
                                                                                    ),
                                                                                ),
                                                                            ),
                                                                            line: 27,
                                                                        ),
                                                                    ),
                                                                ),
                                                                line: 25,
                                                            ),
                                                        ),
                                                        line: 25,
                                                    ),
                                                ),
                                            ),
                                            line: 24,
                                        ),
                                    ),
                                ),
                                line: 23,
                            ),
                        ),
                    ),
                    style: CollectionStyle::Block,
                    line: 1,
                ),
                anchors: [
                    'folders' => $foldersSequence,
                ],
                line: 1,
            ),
        );
    }
}
