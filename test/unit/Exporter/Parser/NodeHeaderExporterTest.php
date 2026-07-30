<?php

declare(strict_types=1);

namespace Purr\Yaml\Test\Exporter\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Purr\Yaml\Parser\NodeHeader;

#[CoversClass(NodeHeaderExporter::class)]
final class NodeHeaderExporterTest extends TestCase
{
    public function testExport_SelectedFields_ReturnsOnlyTheRequestedOnes(): void
    {
        $exporter = new NodeHeaderExporter;
        $header = NodeHeader::extract('&defaults !!str value');

        self::assertSame(
            ['anchor' => 'defaults', 'rest' => 'value'],
            $exporter->export($header, ['anchor', 'rest']),
        );
    }
}
