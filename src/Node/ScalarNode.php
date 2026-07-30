<?php

declare(strict_types=1);

namespace Purr\Yaml\Node;

/**
 * A scalar node holding the already unescaped/unfolded text of the scalar.
 */
final class ScalarNode extends AbstractNode
{
    public function __construct(
        private readonly string $value,
        private readonly ScalarStyle $style = ScalarStyle::Plain,
        ?string $anchor = null,
        ?Tag $tag = null,
        int $line = 0,
    ) {
        parent::__construct($anchor, $tag, $line);
    }

    public static function null(int $line = 0, ?string $anchor = null, ?Tag $tag = null): self
    {
        return new self('', ScalarStyle::Plain, $anchor, $tag, $line);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getStyle(): ScalarStyle
    {
        return $this->style;
    }

    public function accept(NodeVisitor $visitor): mixed
    {
        return $visitor->visitScalar($this);
    }
}
