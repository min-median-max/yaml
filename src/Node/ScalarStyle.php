<?php

declare(strict_types=1);

namespace Purr\Yaml\Node;

/**
 * How a scalar value was written in the source YAML.
 *
 * Records the syntax the parser saw, not a different node kind. Quoted and block
 * scalars always denote strings; only plain scalars may be resolved to bool, int,
 * float, or null by the schema.
 */
enum ScalarStyle
{
    /** Unquoted scalar (`key: value`); subject to schema type resolution. */
    case Plain;

    /** Single-quoted string (`'text'`); only `''` escapes a single quote. */
    case SingleQuoted;

    /** Double-quoted string (`"text"`); supports YAML escape sequences. */
    case DoubleQuoted;

    /** Block scalar with literal style (`|`); newlines are preserved. */
    case Literal;

    /** Block scalar with folded style (`>`); line breaks fold to spaces. */
    case Folded;

    /**
     * Whether the schema may resolve this scalar to a non-string type.
     *
     * Quoted and block scalars always denote strings; only plain scalars are type-resolved.
     */
    public function isTypeResolvable(): bool
    {
        return self::Plain === $this;
    }

    /** Whether the scalar was written with single or double quotes. */
    public function isQuoted(): bool
    {
        return self::SingleQuoted === $this || self::DoubleQuoted === $this;
    }

    /** Whether the scalar was written as a literal (`|`) or folded (`>`) block. */
    public function isBlock(): bool
    {
        return self::Literal === $this || self::Folded === $this;
    }
}
