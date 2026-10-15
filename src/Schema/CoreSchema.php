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
    // The patterns follow the tag resolution table of YAML 1.2.2, section 10.3.2, exactly:
    // the letter cases are the listed ones only, and only decimal integers take a sign.
    private const string NULL_PATTERN = '/^(?:null|Null|NULL|~)$/';
    private const string TRUE_PATTERN = '/^(?:true|True|TRUE)$/';
    private const string FALSE_PATTERN = '/^(?:false|False|FALSE)$/';
    private const string DECIMAL_PATTERN = '/^([-+]?)([0-9]+)$/';
    private const string OCTAL_PATTERN = '/^0o([0-7]+)$/';
    private const string HEXADECIMAL_PATTERN = '/^0x([0-9a-fA-F]+)$/';
    private const string FLOAT_PATTERN = '/^[-+]?(?:\.[0-9]+|[0-9]+(?:\.[0-9]*)?)(?:[eE][-+]?[0-9]+)?$/';
    private const string INFINITY_PATTERN = '/^([-+]?)\.(?:inf|Inf|INF)$/';
    private const string NAN_PATTERN = '/^\.(?:nan|NaN|NAN)$/';

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

        if (1 === preg_match(self::DECIMAL_PATTERN, $value, $matches)) {
            // Leading zeros are decimal digits in the core schema, not an octal prefix.
            $digits = ltrim($matches[2], '0');
            $integer = filter_var($matches[1].('' === $digits ? '0' : $digits), FILTER_VALIDATE_INT);

            return false === $integer ? (float) $value : $integer;
        }

        if (1 === preg_match(self::OCTAL_PATTERN, $value, $matches)) {
            return (int) octdec($matches[1]);
        }

        if (1 === preg_match(self::HEXADECIMAL_PATTERN, $value, $matches)) {
            return (int) hexdec($matches[1]);
        }

        if (1 === preg_match(self::INFINITY_PATTERN, $value, $matches)) {
            return '-' === $matches[1] ? -INF : INF;
        }

        if (1 === preg_match(self::NAN_PATTERN, $value)) {
            return NAN;
        }

        if (1 === preg_match(self::FLOAT_PATTERN, $value)) {
            return (float) $value;
        }

        return $value;
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
