<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Value;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Exception\ReferenceException;
use Purr\Yaml\Exception\ValueException;
use Purr\Yaml\Parser\Parser;
use Purr\Yaml\Test\Fixture\UpperCaseSchema;
use Purr\Yaml\Value\ValueBuilder;

#[CoversClass(ValueBuilder::class)]
#[CoversClass(ReferenceException::class)]
#[CoversClass(ValueException::class)]
final class ValueBuilderTest extends TestCase
{
    public function testBuild_FlatMapping_ReturnsAnAssociativeArray(): void
    {
        $yaml = <<<'YAML'
            name: purrphp
            version: 1
            stable: true
            YAML;
        $document = (new Parser)->parse($yaml)->findFirst();

        self::assertNotNull($document);
        self::assertSame(
            ['name' => 'purrphp', 'version' => 1, 'stable' => true],
            (new ValueBuilder)->build($document->getRoot()),
        );
    }

    public function testBuild_BlockSequence_ReturnsAList(): void
    {
        $yaml = <<<'YAML'
            - one
            - two
            - three
            YAML;
        $document = (new Parser)->parse($yaml)->findFirst();

        self::assertNotNull($document);
        self::assertSame(
            ['one', 'two', 'three'],
            (new ValueBuilder)->build($document->getRoot()),
        );
    }

    public function testBuild_ReferenceToAnAnchoredScalar_ResolvesItToTheAnchoredValue(): void
    {
        $yaml = <<<'YAML'
            original: &value 42
            copy: *value
            YAML;
        $document = (new Parser)->parse($yaml)->findFirst();

        self::assertNotNull($document);
        self::assertSame(
            ['original' => 42, 'copy' => 42],
            (new ValueBuilder)->build($document->getRoot()),
        );
    }

    public function testBuild_ReferenceToAnAnchoredMapping_ResolvesItToTheAnchoredValue(): void
    {
        $yaml = <<<'YAML'
            defaults: &defaults
              adapter: postgres
              host: localhost
            development: *defaults
            YAML;
        $document = (new Parser)->parse($yaml)->findFirst();

        self::assertNotNull($document);
        self::assertSame(
            [
                'defaults' => ['adapter' => 'postgres', 'host' => 'localhost'],
                'development' => ['adapter' => 'postgres', 'host' => 'localhost'],
            ],
            (new ValueBuilder)->build($document->getRoot()),
        );
    }

    public function testBuild_MergeKeyPointingAtAnAnchoredMapping_AddsTheAnchoredKeys(): void
    {
        $yaml = <<<'YAML'
            defaults: &defaults
              adapter: postgres
              host: localhost
            development:
              <<: *defaults
              database: dev
            YAML;
        $document = (new Parser)->parse($yaml)->findFirst();

        self::assertNotNull($document);
        self::assertSame(
            [
                'defaults' => ['adapter' => 'postgres', 'host' => 'localhost'],
                'development' => [
                    'adapter' => 'postgres',
                    'host' => 'localhost',
                    'database' => 'dev',
                ],
            ],
            (new ValueBuilder)->build($document->getRoot()),
        );
    }

    public function testBuild_MergedKeyAlsoDefinedInTheMapping_KeepsTheOwnValue(): void
    {
        $yaml = <<<'YAML'
            defaults: &defaults
              host: localhost
              port: 5432
            production:
              <<: *defaults
              host: db.example.com
            YAML;
        $document = (new Parser)->parse($yaml)->findFirst();

        self::assertNotNull($document);
        self::assertSame(
            [
                'defaults' => ['host' => 'localhost', 'port' => 5432],
                'production' => ['host' => 'db.example.com', 'port' => 5432],
            ],
            (new ValueBuilder)->build($document->getRoot()),
        );
    }

    public function testBuild_MergeKeyWithSeveralSources_LetsTheFirstSourceWinForConflictingKeys(): void
    {
        $yaml = <<<'YAML'
            first: &first
              a: 1
              b: 1
            second: &second
              b: 2
              c: 2
            result:
              <<: [*first, *second]
            YAML;
        $document = (new Parser)->parse($yaml)->findFirst();

        self::assertNotNull($document);
        self::assertSame(
            [
                'first' => ['a' => 1, 'b' => 1],
                'second' => ['b' => 2, 'c' => 2],
                'result' => ['a' => 1, 'b' => 1, 'c' => 2],
            ],
            (new ValueBuilder)->build($document->getRoot()),
        );
    }

    public function testBuild_ComposeLikeConfiguration_ReturnsTheWholeStructure(): void
    {
        $yaml = <<<'YAML'
            version: "3.8"

            x-defaults: &defaults
              restart: unless-stopped
              networks:
                - backend

            services:
              api:
                <<: *defaults
                image: purrphp/api:1.2
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
        $document = (new Parser)->parse($yaml)->findFirst();

        self::assertNotNull($document);
        self::assertSame(
            [
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
            ],
            (new ValueBuilder)->build($document->getRoot()),
        );
    }

    public function testBuild_CustomSchema_UsesItToResolveScalars(): void
    {
        $document = (new Parser)->parse("name: purrphp\nversion: 1")->findFirst();

        self::assertNotNull($document);
        self::assertSame(
            ['NAME' => 'PURRPHP', 'VERSION' => 1],
            (new ValueBuilder(new UpperCaseSchema))->build($document->getRoot()),
        );
    }

    public function testBuild_MergeKeyPointingAtAScalar_ThrowsReferenceException(): void
    {
        $document = (new Parser)->parse("base: &base scalar\nresult:\n  <<: *base")->findFirst();

        self::assertNotNull($document);
        $this->expectException(ReferenceException::class);
        $this->expectExceptionMessage('Merge key "<<" expects a mapping or a sequence of mappings, got scalar');

        (new ValueBuilder)->build($document->getRoot());
    }

    public function testBuild_CollectionUsedAsAMappingKey_ThrowsValueException(): void
    {
        $document = (new Parser)->parse('[a, b]: value')->findFirst();

        self::assertNotNull($document);
        $this->expectException(ValueException::class);
        $this->expectExceptionMessage('A sequence cannot be used as a mapping key');

        (new ValueBuilder)->build($document->getRoot());
    }
}
