<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Exporter\Parser;

use Purr\Yaml\Parser\NodeHeader;

final class NodeHeaderExporter
{
    /**
     * @param list<string> $fields
     *
     * @return array{anchor: ?string, tag: ?string, rest: string}
     */
    public function export(NodeHeader $header, array $fields = []): array
    {
        $exported = [
            'anchor' => $header->findAnchor(),
            'tag' => $header->findTag()?->getValue(),
            'rest' => $header->getRest(),
        ];

        if ([] === $fields) {
            return $exported;
        }

        return array_intersect_key($exported, array_flip($fields));
    }
}
