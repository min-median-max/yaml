<?php

declare(strict_types=1);

namespace Purr\Yaml\Schema;

use const FILTER_VALIDATE_INT;
use const INF;

use function is_float;
use function is_int;

use const NAN;

use Purr\Yaml\Exception\ParseException;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\Node\Tag;
use Purr\Yaml\Node\TagType;

use function sprintf;

/**
 * The YAML 1.2 core schema: null, bool, int, float and string, plus the `!!binary` tag.
 *
 * Only plain (unquoted, untagged) scalars are type resolved; quoted and block scalars
 * are always strings.
 */
final class CoreSchema implements SchemaInterface
{
    private const string NULL_PATTERN = '/^(?:~|null)$/i';
    private const string TRUE_PATTERN = '/^true$/i';
    private const string FALSE_PATTERN = '/^false$/i';
    private const string DECIMAL_PATTERN = '/^[-+]?[0-9]+$/';
    private const string OCTAL_PATTERN = '/^([-+]?)0o([0-7]+)$/';
    private const string HEXADECIMAL_PATTERN = '/^([-+]?)0x([0-9a-f]+)$/i';
    private const string FLOAT_PATTERN = '/^[-+]?(?:[0-9]+\.[0-9]*|\.[0-9]+|[0-9]+)(?:[eE][-+]?[0-9]+)?$/';
    private const string INFINITY_PATTERN = '/^([-+])?\.inf$/i';
    private const string NAN_PATTERN = '/^\.nan$/i';

    public function resolve(ScalarNode $node): mixed
    {
        $tag = $node->findTag();

        if (null !== $tag && $tag->isStandard()) {
            return $this->resolveTagged($node, $tag);
        }

        if (!$node->getStyle()->isTypeResolvable()) {
            return $node->getValue();
        }

        return $this->resolvePlain($node->getValue());
    }

    private function resolveTagged(ScalarNode $node, Tag $tag): mixed
    {
        $value = $node->getValue();

        return match ($tag->findType()) {
            TagType::Str => $value,
            TagType::Null => null,
            TagType::Bool => $this->toBool($value, $node->getLine()),
            TagType::Int => (int) $this->resolveNumeric($value, $node->getLine()),
            TagType::Float => (float) $this->resolveNumeric($value, $node->getLine()),
            TagType::Binary => $this->decodeBinary($value, $node->getLine()),
            default => $node->getStyle()->isTypeResolvable() ? $this->resolvePlain($value) : $value,
        };
    }

    private function resolvePlain(string $value): bool|float|int|string|null
    {
        if ('' === $value || 1 === preg_match(self::NULL_PATTERN, $value)) {
            return null;
        }

        if (1 === preg_match(self::TRUE_PATTERN, $value)) {
            return true;
        }

        if (1 === preg_match(self::FALSE_PATTERN, $value)) {
            return false;
        }

        if (1 === preg_match(self::DECIMAL_PATTERN, $value)) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);

            return false === $integer ? (float) $value : $integer;
        }

        if (1 === preg_match(self::OCTAL_PATTERN, $value, $matches)) {
            return $this->applySign($matches[1], (int) octdec($matches[2]));
        }

        if (1 === preg_match(self::HEXADECIMAL_PATTERN, $value, $matches)) {
            return $this->applySign($matches[1], (int) hexdec($matches[2]));
        }

        if (1 === preg_match(self::INFINITY_PATTERN, $value, $matches)) {
            return '-' === ($matches[1] ?? '') ? -INF : INF;
        }

        if (1 === preg_match(self::NAN_PATTERN, $value)) {
            return NAN;
        }

        if (1 === preg_match(self::FLOAT_PATTERN, $value)) {
            return (float) $value;
        }

        return $value;
    }

    private function applySign(string $sign, int $number): int
    {
        return '-' === $sign ? -$number : $number;
    }

    private function resolveNumeric(string $value, int $line): float|int
    {
        $resolved = $this->resolvePlain($value);

        if (!is_int($resolved) && !is_float($resolved)) {
            throw new ParseException(sprintf('Value "%s" cannot be read as a number', $value), $line);
        }

        return $resolved;
    }

    private function toBool(string $value, int $line): bool
    {
        if (1 === preg_match(self::TRUE_PATTERN, $value)) {
            return true;
        }

        if (1 === preg_match(self::FALSE_PATTERN, $value)) {
            return false;
        }

        throw new ParseException(sprintf('Value "%s" cannot be read as a boolean', $value), $line);
    }

    private function decodeBinary(string $value, int $line): string
    {
        $decoded = base64_decode(preg_replace('/\s+/', '', $value) ?? $value, true);

        if (false === $decoded) {
            throw new ParseException('Value cannot be read as base64 encoded binary data', $line);
        }

        return $decoded;
    }
}
