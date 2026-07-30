<?php

declare(strict_types=1);

namespace Purr\Yaml\Node;

/**
 * How a sequence or mapping was written in the source YAML.
 *
 * Block and flow are alternative notations for the same logical collection type;
 * this enum records which syntax the parser saw, not a different node kind.
 */
enum CollectionStyle
{
    /** Multi-line indentation-based notation (`- item` sequences, indented `key: value` mappings). */
    case Block;

    /** Inline bracket/brace notation (`[item]` sequences, `{key: value}` mappings). */
    case Flow;
}
