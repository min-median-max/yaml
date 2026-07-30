<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Exception\InvalidDocumentsCountException;
use Purr\Yaml\Exception\IoException;
use Purr\Yaml\Exception\ParseException;
use Purr\Yaml\Exception\ReferenceException;
use Purr\Yaml\Exception\ValueException;
use Purr\Yaml\Exception\YamlException;

#[CoversClass(ParseException::class)]
#[CoversClass(InvalidDocumentsCountException::class)]
#[CoversClass(IoException::class)]
#[CoversClass(ReferenceException::class)]
#[CoversClass(ValueException::class)]
final class ExceptionTest extends TestCase
{
    public function testLine_WithLineAndSnippet_ReturnsTheSourceLine(): void
    {
        $exception = new ParseException('Invalid syntax', 5, 'key: value');

        self::assertSame(5, $exception->line());
        self::assertStringContainsString('Invalid syntax', $exception->getMessage());
        self::assertStringContainsString('near "key: value"', $exception->getMessage());
        self::assertStringContainsString('line 5', $exception->getMessage());
        self::assertInstanceOf(YamlException::class, $exception);
    }

    public function testSnippet_WithSnippet_ReturnsTheSourceSnippet(): void
    {
        $exception = new ParseException('Invalid syntax', 5, 'key: value');

        self::assertSame('key: value', $exception->snippet());
    }

    public function testLine_WithoutLineOrSnippet_ReturnsZero(): void
    {
        $exception = new ParseException('Something went wrong');

        self::assertSame(0, $exception->line());
        self::assertSame('Something went wrong.', $exception->getMessage());
    }

    public function testSnippet_WithoutSnippet_ReturnsNull(): void
    {
        $exception = new ParseException('Something went wrong');

        self::assertNull($exception->snippet());
    }

    public function testGetExpects_InvalidDocumentsCount_ReturnsTheExpectedCount(): void
    {
        $exception = new InvalidDocumentsCountException(expects: 1, current: 3);

        self::assertSame(1, $exception->getExpects());
        self::assertStringContainsString('Expects: 1', $exception->getMessage());
        self::assertInstanceOf(YamlException::class, $exception);
    }

    public function testGetCurrent_InvalidDocumentsCount_ReturnsTheCurrentCount(): void
    {
        $exception = new InvalidDocumentsCountException(expects: 1, current: 3);

        self::assertSame(3, $exception->getCurrent());
        self::assertStringContainsString('Current: 3', $exception->getMessage());
    }

    public function testUnreadable_Path_BuildsTheMessage(): void
    {
        $exception = IoException::unreadable('/tmp/missing.yaml');

        self::assertStringContainsString('/tmp/missing.yaml', $exception->getMessage());
        self::assertStringContainsString('not readable', $exception->getMessage());
        self::assertInstanceOf(YamlException::class, $exception);
    }

    public function testUnwritable_Path_BuildsTheMessage(): void
    {
        $exception = IoException::unwritable('/tmp/readonly.yaml');

        self::assertStringContainsString('/tmp/readonly.yaml', $exception->getMessage());
        self::assertStringContainsString('not writable', $exception->getMessage());
        self::assertInstanceOf(YamlException::class, $exception);
    }

    public function testUndefinedAnchor_Name_BuildsTheMessage(): void
    {
        $exception = ReferenceException::undefinedAnchor('missing', 7);

        self::assertStringContainsString('*missing', $exception->getMessage());
        self::assertStringContainsString('line 7', $exception->getMessage());
        self::assertInstanceOf(YamlException::class, $exception);
    }

    public function testNotMergeable_Type_BuildsTheMessage(): void
    {
        $exception = ReferenceException::notMergeable('scalar', 4);

        self::assertStringContainsString('scalar', $exception->getMessage());
        self::assertStringContainsString('line 4', $exception->getMessage());
        self::assertInstanceOf(YamlException::class, $exception);
    }

    public function testUnsupportedKey_Type_BuildsTheMessage(): void
    {
        $exception = ValueException::unsupportedKey('sequence', 2);

        self::assertStringContainsString('sequence', $exception->getMessage());
        self::assertStringContainsString('line 2', $exception->getMessage());
        self::assertInstanceOf(YamlException::class, $exception);
    }
}
