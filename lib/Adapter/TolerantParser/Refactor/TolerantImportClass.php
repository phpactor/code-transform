<?php

namespace Phpactor\CodeTransform\Adapter\TolerantParser\Refactor;

use Phpactor\CodeTransform\Domain\Refactor\ImportClass;
use Microsoft\PhpParser\Parser;
use Phpactor\CodeTransform\Domain\ClassFinder\ClassFinder;
use Phpactor\CodeTransform\Domain\SourceCode;
use Microsoft\PhpParser\Node\QualifiedName;
use Phpactor\CodeTransform\Domain\Exception\TransformException;
use Phpactor\CodeTransform\Domain\ClassNames;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\ClassAlreadyImportedException;
use Phpactor\CodeTransform\Domain\ClassName;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Microsoft\PhpParser\Node;
use Phpactor\CodeBuilder\Domain\Code;

class TolerantImportClass implements ImportClass
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Updater
     */
    private $updater;

    public function __construct(Updater $updater, Parser $parser = null)
    {
        $this->parser = $parser ?: new Parser();;
        $this->updater = $updater;
    }

    public function importClass(SourceCode $source, int $offset, string $name = null)
    {
        $name = ClassName::fromString($name);
        $sourceNode = $this->parser->parseSourceFile($source);
        $node = $sourceNode->getDescendantNodeAtPosition($offset);

        if (false === $node instanceof QualifiedName) {
            throw new TransformException(sprintf(
                'Node at offset "%s" is not a qualified name', $offset
            ));
        }

        $this->checkIfAlreadyImported($node, $name);

        return $this->addImport($source, $node, $name);
    }

    private function nameFromQualifiedName(QualifiedName $node): string
    {
        $parts = $node->getNameParts();

        if (count($parts) === 0) {
            throw new TransformException(sprintf(
                'Name must have at least one part (this shouldn\'t happen'
            ));
        }

        $name = array_shift($parts);

        return $name->getText($node->getFileContents());
    }

    private function checkIfAlreadyImported(QualifiedName $node, ClassName $className)
    {
        $imports = $node->getImportTablesForCurrentScope()[0];

        if (false === isset($imports[(string) $className])) {
            return;
        }

        throw new ClassAlreadyImportedException($baseShortName);
    }

    private function addImport(SourceCode $source, Node $node, string $name): SourceCode
    {
        $builder = SourceCodeBuilder::create();
        $builder->use($name);
        $prototype = $builder->build();

        return $source->withSource($this->updater->apply($prototype, Code::fromString((string) $source)));
    }
}
