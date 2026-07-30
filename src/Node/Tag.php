<?php

declare(strict_types=1);

namespace Purr\Yaml\Node;

use Stringable;

use function strlen;

/**
 * A node tag, either a standard YAML tag (`!!str`) or an application specific one (`!point`).
 */
final class Tag implements Stringable
{
    private const string YAML_PREFIX = 'tag:yaml.org,2002:';

    private function __construct(private readonly string $value) {}

    public function __toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $tag): self
    {
        return new self($tag);
    }

    /**
     * The tag exactly as it was written in the source.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * The short name of a standard tag (`str` for `!!str`), or null for application tags.
     */
    public function findName(): ?string
    {
        if (str_starts_with($this->value, '!!')) {
            return substr($this->value, 2);
        }

        if (str_starts_with($this->value, '!<'.self::YAML_PREFIX)) {
            return substr($this->value, 2 + strlen(self::YAML_PREFIX), -1);
        }

        return null;
    }

    public function isStandard(): bool
    {
        return null !== $this->findName();
    }

    /**
     * The known standard tag type, or null for application tags and unrecognized names.
     */
    public function findType(): ?TagType
    {
        $name = $this->findName();

        return null !== $name ? TagType::tryFrom($name) : null;
    }

    public function is(TagType $name): bool
    {
        return $name === $this->findType();
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
