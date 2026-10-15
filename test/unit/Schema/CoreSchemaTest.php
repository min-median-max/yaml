<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Schema;

use const INF;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Exception\ParseException;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Node\ScalarStyle;
use Purr\Yaml\Node\Tag;
use Purr\Yaml\Schema\CoreSchema;

#[CoversClass(CoreSchema::class)]
final class CoreSchemaTest extends TestCase
{
    #[DataProvider('plainScalars')]
    public function testResolve_PlainScalarValue_ResolvesItToAPhpType(string $value, mixed $expected): void
    {
        $node = new ScalarNode(value: $value, line: 1);

        self::assertSame($expected, (new CoreSchema)->resolve($node));
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function plainScalars(): iterable
    {
        yield 'string' => ['hello world', 'hello world'];
        yield 'empty' => ['', null];
        yield 'tilde' => ['~', null];
        yield 'null' => ['null', null];
        yield 'null uppercase' => ['NULL', null];
        yield 'true' => ['true', true];
        yield 'true capitalized' => ['True', true];
        yield 'false' => ['false', false];
        yield 'yaml 1.1 yes stays a string' => ['yes', 'yes'];
        yield 'yaml 1.1 on stays a string' => ['on', 'on'];
        yield 'integer' => ['42', 42];
        yield 'negative integer' => ['-17', -17];
        yield 'octal' => ['0o14', 12];
        yield 'hexadecimal' => ['0xFF', 255];
        yield 'float' => ['3.14', 3.14];
        yield 'exponent' => ['1.2e3', 1200.0];
        yield 'leading zero decimal is an int' => ['0777', 777];
        yield 'signed leading zero decimal is an int' => ['-007', -7];
        yield 'signed hexadecimal stays a string' => ['+0x1', '+0x1'];
        yield 'negative hexadecimal stays a string' => ['-0x1', '-0x1'];
        yield 'signed octal stays a string' => ['-0o7', '-0o7'];
        yield 'positive octal stays a string' => ['+0o7', '+0o7'];
        yield 'uppercase hexadecimal prefix stays a string' => ['0X1F', '0X1F'];
        yield 'uppercase octal prefix stays a string' => ['0O17', '0O17'];
        yield 'null capitalized' => ['Null', null];
        yield 'mixed case null stays a string' => ['nULL', 'nULL'];
        yield 'mixed case true stays a string' => ['tRUE', 'tRUE'];
        yield 'mixed case false stays a string' => ['fALSE', 'fALSE'];
        yield 'false uppercase' => ['FALSE', false];
        yield 'mixed case infinity stays a string' => ['.iNf', '.iNf'];
        yield 'mixed case not a number stays a string' => ['.nAn', '.nAn'];
        yield 'binary like stays a string' => ['0b101', '0b101'];
        yield 'underscore int like stays a string' => ['1_000', '1_000'];
    }

    public function testResolve_InfinityAndNotANumberScalars_ResolvesThemToFloatConstants(): void
    {
        $schema = new CoreSchema;

        self::assertSame(INF, $schema->resolve(new ScalarNode(value: '.inf', line: 1)));
        self::assertSame(-INF, $schema->resolve(new ScalarNode(value: '-.Inf', line: 1)));
        self::assertNan($schema->resolve(new ScalarNode(value: '.nan', line: 1)));
    }

    public function testResolve_IntegerBeyondThePlatformRange_ResolvesItToAFloat(): void
    {
        self::assertSame(
            9.2233720368547758e18,
            (new CoreSchema)->resolve(new ScalarNode(value: '9223372036854775808', line: 1)),
        );
    }

    public function testResolve_QuotedScalarValue_KeepsItAsAString(): void
    {
        $node = new ScalarNode(value: '42', style: ScalarStyle::DoubleQuoted, line: 1);

        self::assertSame('42', (new CoreSchema)->resolve($node));
    }

    #[DataProvider('taggedScalars')]
    public function testResolve_TaggedScalarValue_ResolvesItAccordingToTheTag(
        string $value,
        string $tag,
        mixed $expected,
    ): void {
        $node = new ScalarNode(value: $value, tag: Tag::fromString($tag), line: 1);

        self::assertSame($expected, (new CoreSchema)->resolve($node));
    }

    /**
     * @return iterable<string, array{string, string, mixed}>
     */
    public static function taggedScalars(): iterable
    {
        yield 'string tag' => ['42', '!!str', '42'];
        yield 'integer tag' => ['42', '!!int', 42];
        yield 'float tag' => ['42', '!!float', 42.0];
        yield 'null tag' => ['anything', '!!null', null];
        yield 'binary tag' => ['aGVsbG8=', '!!binary', 'hello'];
        yield 'boolean true tag' => ['true', '!!bool', true];
        yield 'boolean false tag' => ['false', '!!bool', false];
    }

    public function testResolve_InvalidTaggedBoolean_ThrowsParseException(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('cannot be read as a boolean');

        (new CoreSchema)->resolve(new ScalarNode(value: 'maybe', tag: Tag::fromString('!!bool'), line: 1));
    }

    public function testResolve_UnknownStandardTag_FallsBackToPlainResolution(): void
    {
        self::assertSame(
            42,
            (new CoreSchema)->resolve(new ScalarNode(value: '42', tag: Tag::fromString('!!map'), line: 1)),
        );
    }

    public function testResolve_InvalidTaggedNumber_ThrowsParseException(): void
    {
        $this->expectException(ParseException::class);

        (new CoreSchema)->resolve(new ScalarNode(value: 'not-a-number', tag: Tag::fromString('!!int'), line: 1));
    }
}
