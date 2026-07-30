<?php

declare(strict_types=1);

namespace Purr\Yaml\Test;

use const INF;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Document;
use Purr\Yaml\Exception\ReferenceException;
use Purr\Yaml\Exception\ValueException;
use Purr\Yaml\Node\MappingEntry;
use Purr\Yaml\Node\MappingEntryList;
use Purr\Yaml\Node\MappingNode;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Node\SequenceNode;
use Purr\Yaml\Parser\Parser;
use Purr\Yaml\Schema\CoreSchema;
use Purr\Yaml\Test\Fixture\UpperCaseSchema;

#[CoversClass(Document::class)]
final class DocumentTest extends TestCase
{
    #[DataProvider('scalars')]
    public function testValue_ScalarValue_ResolvesItToAPhpType(string $yaml, mixed $expected): void
    {
        $document = (new Parser)->parse($yaml)->findFirst();

        self::assertNotNull($document);
        self::assertSame(['value' => $expected], $document->getValue());
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function scalars(): iterable
    {
        yield 'string' => ['value: hello world', 'hello world'];
        yield 'empty' => ['value:', null];
        yield 'tilde' => ['value: ~', null];
        yield 'null' => ['value: null', null];
        yield 'null uppercase' => ['value: NULL', null];
        yield 'true' => ['value: true', true];
        yield 'true capitalized' => ['value: True', true];
        yield 'false' => ['value: false', false];
        yield 'yaml 1.1 yes stays a string' => ['value: yes', 'yes'];
        yield 'yaml 1.1 on stays a string' => ['value: on', 'on'];
        yield 'integer' => ['value: 42', 42];
        yield 'negative integer' => ['value: -17', -17];
        yield 'octal' => ['value: 0o14', 12];
        yield 'hexadecimal' => ['value: 0xFF', 255];
        yield 'float' => ['value: 3.14', 3.14];
        yield 'exponent' => ['value: 1.2e3', 1200.0];
        yield 'quoted number stays a string' => ['value: "42"', '42'];
        yield 'quoted boolean stays a string' => ["value: 'true'", 'true'];
        yield 'single quotes unescape' => ["value: 'it''s here'", "it's here"];
        yield 'double quotes unescape' => ['value: "line\nbreak"', "line\nbreak"];
        yield 'unicode escape' => ['value: "\u00e9t\u00e9"', 'été'];
        yield 'colon inside a value' => ['value: http://example.com', 'http://example.com'];
        yield 'hash without space is not a comment' => ['value: pass#word', 'pass#word'];
        yield 'comment is removed' => ['value: kept # dropped', 'kept'];
        yield 'hash inside quotes is kept' => ['value: "kept # here"', 'kept # here'];
        yield 'multi line plain scalar folds' => ["value: one\n  two\n  three", 'one two three'];
        yield 'explicit string tag' => ['value: !!str 42', '42'];
        yield 'explicit integer tag' => ['value: !!int 42', 42];
        yield 'explicit float tag' => ['value: !!float 42', 42.0];
        yield 'explicit null tag' => ['value: !!null anything', null];
        yield 'binary tag' => ['value: !!binary aGVsbG8=', 'hello'];
    }

    public function testValue_InfinityAndNotANumberScalars_ResolvesThemToFloatConstants(): void
    {
        $document = (new Parser)->parse("positive: .inf\nnegative: -.Inf\nundefined: .nan")->findFirst();

        self::assertNotNull($document);
        /** @var array{positive: float, negative: float, undefined: float} $value */
        $value = $document->getValue();

        self::assertSame(INF, $value['positive']);
        self::assertSame(-INF, $value['negative']);
        self::assertNan($value['undefined']);
    }

    public function testValue_IntegerBeyondThePlatformRange_ResolvesItToAFloat(): void
    {
        $document = (new Parser)->parse('value: 9223372036854775808')->findFirst();

        self::assertNotNull($document);
        self::assertSame(['value' => 9.2233720368547758e18], $document->getValue());
    }

    public function testValue_ComposeLikeConfiguration_ReturnsTheWholeStructure(): void
    {
        $yaml = <<<'YAML'
            # deployment description
            version: "3.8"

            x-defaults: &defaults
              restart: unless-stopped
              networks:
                - backend

            services:
              api:
                <<: *defaults
                image: purrphp/api:1.2   # pinned
                ports:
                  - "8080:80"
                  - "8443:443"
                environment:
                  APP_DEBUG: false
                  APP_LEVEL: 3
                command: >-
                  php -S 0.0.0.0:80
                  public/index.php
              worker:
                <<: *defaults
                image: purrphp/worker:1.2
                entrypoint: |
                  #!/bin/sh
                  exec worker --verbose

            networks:
              backend: {}
            YAML;

        $expected = [
            'version' => '3.8',
            'x-defaults' => [
                'restart' => 'unless-stopped',
                'networks' => ['backend'],
            ],
            'services' => [
                'api' => [
                    'restart' => 'unless-stopped',
                    'networks' => ['backend'],
                    'image' => 'purrphp/api:1.2',
                    'ports' => ['8080:80', '8443:443'],
                    'environment' => ['APP_DEBUG' => false, 'APP_LEVEL' => 3],
                    'command' => 'php -S 0.0.0.0:80 public/index.php',
                ],
                'worker' => [
                    'restart' => 'unless-stopped',
                    'networks' => ['backend'],
                    'image' => 'purrphp/worker:1.2',
                    'entrypoint' => "#!/bin/sh\nexec worker --verbose\n",
                ],
            ],
            'networks' => ['backend' => []],
        ];

        $document = (new Parser)->parse($yaml)->findFirst();

        self::assertNotNull($document);
        self::assertSame($expected, $document->getValue());
    }

    public function testValue_PipelineLikeConfiguration_ReturnsTheWholeStructure(): void
    {
        $yaml = <<<'YAML'
            .job: &job
              image: php:8.1
              before_script:
                - composer install

            stages: [build, test]

            test:
              <<: *job
              stage: test
              script:
                - make test-unit
                - make analyse
              rules:
                - if: $CI_COMMIT_BRANCH
                  when: always
            YAML;

        $expected = [
            '.job' => [
                'image' => 'php:8.1',
                'before_script' => ['composer install'],
            ],
            'stages' => ['build', 'test'],
            'test' => [
                'image' => 'php:8.1',
                'before_script' => ['composer install'],
                'stage' => 'test',
                'script' => ['make test-unit', 'make analyse'],
                'rules' => [
                    ['if' => '$CI_COMMIT_BRANCH', 'when' => 'always'],
                ],
            ],
        ];

        $document = (new Parser)->parse($yaml)->findFirst();

        self::assertNotNull($document);
        self::assertSame($expected, $document->getValue());
    }

    public function testValue_CustomSchema_UsesItToResolveScalars(): void
    {
        $document = (new Parser)->parse("name: purrphp\nversion: 1")->findFirst();

        self::assertNotNull($document);
        self::assertSame(
            ['NAME' => 'PURRPHP', 'VERSION' => 1],
            $document->getValue(new UpperCaseSchema),
        );
    }

    public function testValue_CollectionUsedAsAMappingKey_ThrowsValueException(): void
    {
        $this->expectException(ValueException::class);

        (new Parser)->parse('[a, b]: value')->findFirst()?->getValue();
    }

    public function testValue_MergeKeyPointingAtAScalar_ThrowsReferenceException(): void
    {
        $document = (new Parser)->parse("base: &base scalar\nresult:\n  <<: *base")->findFirst();

        self::assertNotNull($document);
        $this->expectException(ReferenceException::class);

        $document->getValue();
    }

    public function testGetAnchors_DocumentWithSeveralAnchors_ReturnsThemInDeclarationOrder(): void
    {
        $document = (new Parser)->parse("a: &first 1\nb: &second [2]")->findFirst();

        self::assertNotNull($document);
        self::assertSame(['first', 'second'], array_keys($document->getAnchors()));
    }

    public function testFindAnchor_DefinedAnchor_ReturnsTheNode(): void
    {
        $document = (new Parser)->parse("a: &first 1\nb: &second [2]")->findFirst();

        self::assertNotNull($document);
        self::assertInstanceOf(SequenceNode::class, $document->findAnchor('second'));
    }

    public function testGetLine_SourceWithSeveralDocuments_ReportsTheDocumentStartLine(): void
    {
        $documents = (new Parser)->parse("---\na: 1\n---\nb: 2");
        $lines = [];

        foreach ($documents as $document) {
            $lines[] = $document->getLine();
        }

        self::assertSame([1, 3], $lines);
    }

    public function testValue_WithCoreSchema_UsesTheDefaultSchema(): void
    {
        $document = (new Parser)->parse('value: 42')->findFirst();

        self::assertNotNull($document);
        self::assertSame(['value' => 42], $document->getValue(new CoreSchema));
    }

    public function testGetRoot_CustomSchema_ReturnsRootNode(): void
    {
        $document = (new Parser)->parse("name: purrphp\nversion: 1")->findFirst();

        self::assertNotNull($document);
        self::assertEquals(
            new MappingNode(
                new MappingEntryList(
                    new MappingEntry(
                        new ScalarNode(value: 'name', line: 1),
                        new ScalarNode(value: 'purrphp', line: 1)
                    ),
                    new MappingEntry(
                        new ScalarNode(value: 'version', line: 2),
                        new ScalarNode(value: '1', line: 2)
                    )
                ),
                line: 1
            ),
            $document->getRoot(),
        );
    }
}
