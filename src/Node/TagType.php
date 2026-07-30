<?php

declare(strict_types=1);

namespace Purr\Yaml\Node;

/**
 * Short names of standard YAML tags (`str` for `!!str`).
 */
enum TagType: string
{
    case Str = 'str';
    case Int = 'int';
    case Float = 'float';
    case Bool = 'bool';
    case Null = 'null';
    case Binary = 'binary';
    case Map = 'map';
    case Seq = 'seq';
}
