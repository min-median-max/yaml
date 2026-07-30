# PurrPHP/Yaml

An object oriented YAML 1.2 parser for PHP 8.3+.

Every part of a YAML source is a class: the stream, its documents and each node
(scalar, sequence, mapping, alias) keeps its style, tag, anchor and source line.
Anchors and references (`&anchor`, `*alias`, `<<:`) are first class citizens — an
alias node links to the very node it points at instead of a copy of its value.

## Installation

```bash
composer require purrphp/yaml
```

## Entry points

`YamlStream` works with any number of documents in a source. `YamlStreamSingle`
expects exactly one document and throws `InvalidDocumentsCountException` otherwise.

```php
use Purr\Yaml\YamlStream;
use Purr\Yaml\YamlStreamSingle;

$stream = new YamlStream();
$single = new YamlStreamSingle();
```

## Parsing to PHP values

```php
use Purr\Yaml\YamlStream;

$yaml = new YamlStream();

$config = $yaml->toPlainArray(<<<'YAML'
    database:
      host: localhost
      ports: [5432, 5433]
    debug: true
    YAML);

// ['database' => ['host' => 'localhost', 'ports' => [5432, 5433]], 'debug' => true]

$documents = $yaml->toPlainArray($multiDocumentSource); // list of values per document
$config    = $yaml->fileToPlainArray('config/services.yaml');
```

With a single-document source, `YamlStreamSingle` is a shorter alternative:

```php
$config = (new YamlStreamSingle())->toPlainArray($source);
$config = (new YamlStreamSingle())->fileToPlainArray('config/services.yaml');
```

## Working with the model

`fromString()` returns a `DocumentList` — the parsed model instead of plain arrays:

```php
use Purr\Yaml\Node\MappingNode;
use Purr\Yaml\Node\ScalarNode;
use Purr\Yaml\YamlStream;

$document = (new YamlStream())->fromString($source)->findFirst();
$root = $document->getRoot();

if ($root instanceof MappingNode) {
    $database = $root->find('database');          // ?AbstractNode
}

if ($database instanceof MappingNode) {
    $host = $database->find('host');
}

if ($host instanceof ScalarNode) {
    $host->getValue();                              // 'localhost' (raw text)
    $host->getStyle();                              // ScalarStyle::Plain
    $host->getLine();                               // source line number
    $host->findTag();                               // ?Tag
}

$document->getValue();                              // the same graph as PHP values
```

| Class | Represents |
| --- | --- |
| `DocumentList` | all documents of one source |
| `Document` | one document plus its anchors |
| `ScalarNode` | a scalar, with its `ScalarStyle` |
| `SequenceNode` | a block or flow sequence |
| `MappingNode` / `MappingEntry` | a block or flow mapping and its pairs |
| `AliasNode` | a `*reference` linked to its anchored node |
| `Tag` | `!!str`, `!custom`, … |

## Dumping back to YAML

The dumper turns the document model back into YAML text, preserving styles, tags
and anchors:

```php
$yaml = new YamlStream();

$documents = $yaml->fromString($source);
$output    = $yaml->toYaml($documents);

$yaml->toFile('config/services.yaml', $documents);

// single document
$single = new YamlStreamSingle();
$document = $single->fromString($source);
$output   = $single->toYaml($document);
$single->toFile('config/services.yaml', $document);
```

## References

```php
$value = (new YamlStream())->toPlainArray(<<<'YAML'
    defaults: &defaults
      adapter: postgres
      host: localhost

    development:
      <<: *defaults
      database: dev

    production:
      <<: *defaults
      host: db.example.com
    YAML);
```

Merge keys behave as the YAML spec describes: the merging mapping wins over the
merged one, and when several sources are merged (`<<: [*a, *b]`) the first one wins.

The links themselves stay visible in the model:

```php
$document = (new YamlStream())->fromString($source)->findFirst();

$root = $document->getRoot();
assert($root instanceof MappingNode);

$development = $root->find('development');
assert($development instanceof MappingNode);

$alias = $development->find('<<');
assert($alias instanceof AliasNode);

$alias->getName();                                             // 'defaults'
$alias->getTarget() === $document->findAnchor('defaults');     // true, the very same object
$document->getAnchors();                                       // ['defaults' => MappingNode]
```

## Visiting nodes

Nodes accept any `NodeVisitor`, which makes it easy to build your own
transformation instead of going through arrays:

```php
use Purr\Yaml\Node\NodeVisitor;

/** @implements NodeVisitor<int> */
final class NodeCounter implements NodeVisitor
{
    public function visitScalar(ScalarNode $node): int
    {
        return 1;
    }

    public function visitSequence(SequenceNode $node): int
    {
        return array_sum(array_map(fn ($item) => $item->accept($this), $node->getItems())) + 1;
    }

    public function visitMapping(MappingNode $node): int
    {
        $count = 1;

        foreach ($node->getEntries() as $entry) {
            $count += $entry->getKey()->accept($this);
            $count += $entry->getValue()->accept($this);
        }

        return $count;
    }

    public function visitAlias(AliasNode $node): int
    {
        return 1;
    }
}

$count = $document->getRoot()->accept(new NodeCounter());
```

Scalar typing is driven by a `Schema`. `CoreSchema` implements the YAML 1.2 core
schema; pass your own to the `YamlStream` constructor (or straight to `Document::getValue()`)
to change how scalars become PHP values:

```php
$yaml = new YamlStream(new MySchema());
```

## Supported syntax

YAML **1.2** only. Sources with a `%YAML` directive must declare version `1.2`;
other versions (such as 1.1) are rejected. Scalar typing follows the YAML 1.2 core
schema (`true`/`false`, not `yes`/`no`).

Block and flow collections, plain, single and double quoted scalars (with escape
sequences), literal `|` and folded `>` block scalars with indentation and chomping
indicators, multi line plain scalars, comments, multiple documents (`---`, `...`),
directives, anchors, aliases, merge keys and tags (`!!str`, `!!int`, `!!float`,
`!!bool`, `!!null`, `!!binary`, plus custom tags kept on the node).

Not supported: explicit keys (`? key`), verbose tag resolution against `%TAG`
directives, and recursive anchors.

## Errors

Every failure implements `Purr\Yaml\Exception\YamlException`:
`ParseException` (with the offending line), `ReferenceException` (undefined anchor
or unmergeable value), `ValueException` (a collection used as a mapping key),
`InvalidDocumentsCountException` (when `YamlStreamSingle` sees other than one document),
and `IoException`.

## Development

```bash
composer install

composer test        # Run tests
composer analyse     # Static analysis
composer cs-check    # Code style check
composer cs-fix      # Fix code style
composer check       # All checks
```

### With Docker / Make

```bash
make test-unit  # Run tests
make analyse    # Static analysis
make cs-check   # Code style check
make check      # All checks
make shell      # Open shell in dev container
make validate   # Check all
```

## License

MIT — see [LICENSE](LICENSE).
