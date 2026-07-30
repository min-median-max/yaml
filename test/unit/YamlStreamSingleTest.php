<?php

declare(strict_types=1);

namespace Purr\Yaml\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Document;
use Purr\Yaml\Exception\InvalidDocumentsCountException;
use Purr\Yaml\Exception\IoException;
use Purr\Yaml\Exception\ParseException;
use Purr\Yaml\Node\CollectionStyle;
use Purr\Yaml\Node\MappingEntry;
use Purr\Yaml\Node\MappingEntryList;
use Purr\Yaml\Node\MappingNode;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Parser\Parser;
use Purr\Yaml\YamlStreamSingle;

#[CoversClass(YamlStreamSingle::class)]
final class YamlStreamSingleTest extends TestCase
{
    public function testFromString_SourceWithOneDocument_ReturnsThatDocument(): void
    {
        $yamlStream = new YamlStreamSingle;

        self::assertEquals(
            self::keyValueDocument(),
            $yamlStream->fromString("key: value\n"),
        );
    }

    public function testFromString_SourceWithSeveralDocuments_ThrowsInvalidDocumentsCountException(): void
    {
        $yamlStream = new YamlStreamSingle;
        $this->expectException(InvalidDocumentsCountException::class);
        $this->expectExceptionMessage('Invalid documents count. Expects: 1. Current: 2');

        try {
            $yamlStream->fromString("---\nname: first\n---\nname: second\n");
        } catch (InvalidDocumentsCountException $exception) {
            self::assertSame(1, $exception->getExpects());
            self::assertSame(2, $exception->getCurrent());

            throw $exception;
        }
    }

    public function testFromString_SourceWithoutContent_ThrowsInvalidDocumentsCountException(): void
    {
        $yamlStream = new YamlStreamSingle;
        $this->expectException(InvalidDocumentsCountException::class);
        $this->expectExceptionMessage('Invalid documents count. Expects: 1. Current: 0');

        $yamlStream->fromString("\n\n# nothing here\n");
    }

    public function testFromString_SourceWithYaml11Directive_ThrowsParseException(): void
    {
        $yamlStream = new YamlStreamSingle;
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unsupported YAML version "1.1"');

        $yamlStream->fromString("%YAML 1.1\n---\nkey: value\n");
    }

    public function testFromString_SourceWithWindowsLineEndings_ReadsItLikeUnixLines(): void
    {
        $yamlStream = new YamlStreamSingle;

        self::assertEquals(
            new Document(
                root: new MappingNode(
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
                line: 1,
            ),
            $yamlStream->fromString("a: 1\r\nb: 2\r\n"),
        );
    }

    public function testFromFile_ReadableFile_ReturnsItsDocument(): void
    {
        $yamlStream = new YamlStreamSingle;
        $path = tempnam(sys_get_temp_dir(), 'yaml');
        self::assertIsString($path);
        file_put_contents($path, "key: value\n");

        try {
            self::assertEquals(self::keyValueDocument(), $yamlStream->fromFile($path));
        } finally {
            unlink($path);
        }
    }

    public function testFromFile_MissingFile_ThrowsIoException(): void
    {
        $yamlStream = new YamlStreamSingle;
        $this->expectException(IoException::class);

        $yamlStream->fromFile(__DIR__.'/does-not-exist.yaml');
    }

    public function testToPlainArray_SourceWithOneDocument_ReturnsItsValue(): void
    {
        $yamlStream = new YamlStreamSingle;

        self::assertSame(['key' => 'value'], $yamlStream->toPlainArray("key: value\n"));
    }

    public function testFileToPlainArray_ReadableFile_ReturnsItsValue(): void
    {
        $yamlStream = new YamlStreamSingle;
        $path = tempnam(sys_get_temp_dir(), 'yaml');
        self::assertIsString($path);
        file_put_contents($path, "key: value\n");

        try {
            self::assertSame(['key' => 'value'], $yamlStream->fileToPlainArray($path));
        } finally {
            unlink($path);
        }
    }

    public function testFromFile_ReadableFile_AlsoReturnsItsDocument(): void
    {
        $yamlStream = new YamlStreamSingle;
        $path = tempnam(sys_get_temp_dir(), 'yaml');
        self::assertIsString($path);
        file_put_contents($path, "key: value\n");

        try {
            self::assertEquals(self::keyValueDocument(), $yamlStream->fromFile($path));
        } finally {
            unlink($path);
        }
    }

    public function testFileToPlainArray_MissingFile_ThrowsIoException(): void
    {
        $yamlStream = new YamlStreamSingle;
        $this->expectException(IoException::class);

        $yamlStream->fileToPlainArray(__DIR__.'/does-not-exist.yaml');
    }

    public function testToYaml_Document_RoundTripsItsContent(): void
    {
        $yamlStream = new YamlStreamSingle;
        $document = (new Parser)->parse("key: value\n")->findFirst();
        self::assertNotNull($document);

        $roundTripped = $yamlStream->fromString($yamlStream->toYaml($document));

        self::assertEquals(self::keyValueDocument()->getRoot(), $roundTripped->getRoot());
    }

    public function testToFile_UnwritablePath_ThrowsIoException(): void
    {
        $yamlStream = new YamlStreamSingle;
        $document = (new Parser)->parse("key: value\n")->findFirst();
        self::assertNotNull($document);
        $path = sys_get_temp_dir().'/purrphp-yaml-single-readonly-'.uniqid('', true).'.yaml';
        file_put_contents($path, "existing\n");
        chmod($path, 0o444);

        try {
            $this->expectException(IoException::class);
            $this->expectExceptionMessage('not writable');

            $yamlStream->toFile($path, $document);
        } finally {
            chmod($path, 0o644);
            @unlink($path);
        }
    }

    public function testToFile_ValidFile_WritesSerializedDocument(): void
    {
        $yamlStream = new YamlStreamSingle;
        $document = (new Parser)->parse("key: value\n")->findFirst();
        self::assertNotNull($document);
        $path = sys_get_temp_dir().'/purrphp-yaml-single-dump-'.uniqid('', true).'.yaml';

        try {
            $yamlStream->toFile($path, $document);

            self::assertEquals(self::keyValueDocument()->getRoot(), $yamlStream->fromFile($path)->getRoot());
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
}
