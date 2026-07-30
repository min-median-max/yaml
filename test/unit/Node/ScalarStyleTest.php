<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Node\ScalarStyle;

#[CoversClass(ScalarStyle::class)]
final class ScalarStyleTest extends TestCase
{
    #[DataProvider('typeResolvableCases')]
    public function testIsTypeResolvable_EachScalarStyle_DescribesWhetherSchemaMayResolveIt(
        ScalarStyle $style,
        bool $expected,
    ): void {
        self::assertSame($expected, $style->isTypeResolvable());
    }

    /**
     * @return iterable<string, array{ScalarStyle, bool}>
     */
    public static function typeResolvableCases(): iterable
    {
        yield 'plain' => [ScalarStyle::Plain, true];
        yield 'single quoted' => [ScalarStyle::SingleQuoted, false];
        yield 'double quoted' => [ScalarStyle::DoubleQuoted, false];
        yield 'literal' => [ScalarStyle::Literal, false];
        yield 'folded' => [ScalarStyle::Folded, false];
    }

    #[DataProvider('quotedCases')]
    public function testIsQuoted_EachScalarStyle_DescribesWhetherItWasQuoted(
        ScalarStyle $style,
        bool $expected,
    ): void {
        self::assertSame($expected, $style->isQuoted());
    }

    /**
     * @return iterable<string, array{ScalarStyle, bool}>
     */
    public static function quotedCases(): iterable
    {
        yield 'plain' => [ScalarStyle::Plain, false];
        yield 'single quoted' => [ScalarStyle::SingleQuoted, true];
        yield 'double quoted' => [ScalarStyle::DoubleQuoted, true];
        yield 'literal' => [ScalarStyle::Literal, false];
        yield 'folded' => [ScalarStyle::Folded, false];
    }

    #[DataProvider('blockCases')]
    public function testIsBlock_EachScalarStyle_DescribesWhetherItIsABlockScalar(
        ScalarStyle $style,
        bool $expected,
    ): void {
        self::assertSame($expected, $style->isBlock());
    }

    /**
     * @return iterable<string, array{ScalarStyle, bool}>
     */
    public static function blockCases(): iterable
    {
        yield 'plain' => [ScalarStyle::Plain, false];
        yield 'single quoted' => [ScalarStyle::SingleQuoted, false];
        yield 'double quoted' => [ScalarStyle::DoubleQuoted, false];
        yield 'literal' => [ScalarStyle::Literal, true];
        yield 'folded' => [ScalarStyle::Folded, true];
    }
}
