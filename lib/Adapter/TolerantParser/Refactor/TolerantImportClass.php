<?php

namespace Phpactor\CodeTransform\Adapter\TolerantParser\Refactor;

use Phpactor\CodeTransform\Domain\Refactor\ImportClass;
use Microsoft\PhpParser\Parser;
use Phpactor\CodeTransform\Domain\SourceCode;
use Microsoft\PhpParser\Node\QualifiedName;
use Phpactor\CodeTransform\Domain\Exception\TransformException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\ClassAlreadyImportedException;
use Phpactor\CodeTransform\Domain\ClassName;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Microsoft\PhpParser\Node;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\AliasAlreadyUsedException;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\ClassIsCurrentClassException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\ClassAlreadyInNamespaceException;

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
        $this->parser = $parser ?: new Parser();
        ;
        $this->updater = $updater;
    }

    public function importClass(SourceCode $source, int $offset, string $name, string $alias = null): SourceCode
    {
        $name = ClassName::fromString($name);
        $sourceNode = $this->parser->parseSourceFile($source);
        $node = $sourceNode->getDescendantNodeAtPosition($offset);

        $this->checkIfAlreadyImported($node, $name, $alias);

        return $this->addImport($source, $node, $name, $alias);
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

    private function checkIfAlreadyImported(Node $node, ClassName $className, string $alias = null)
    {
        $imports = $node->getImportTablesForCurrentScope()[0];

        if (null === $alias && isset($imports[$className->short()])) {
            throw new ClassAlreadyImportedException($className->short(), $imports[$className->short()]);
        }

        if ($alias && isset($imports[$alias])) {
            throw new AliasAlreadyUsedException($alias);
        }

        if ($this->currentClassIsSameAsImportClass($node, $className)) {
            throw new ClassIsCurrentClassException($className->short());
        }

        if ($this->importClassInSameNamespace($node, $className)) {
            throw new ClassAlreadyInNamespaceException($className->short());
        }
    }

    private function addImport(SourceCode $source, Node $node, string $name, string $alias = null): SourceCode
    {
        $builder = SourceCodeBuilder::create();
        $builder->use($name, $alias);
        $prototype = $builder->build();

        return $source->withSource($this->updater->apply($prototype, Code::fromString((string) $source)));
    }

    private function currentClassIsSameAsImportClass(Node $node, ClassName $className): bool
    {
        if ($node instanceof ClassDeclaration) {
            return true;
        }

        /** @var ClassDeclaration|null $classNode */
        $classNode = $node->getFirstAncestor(ClassDeclaration::class);

        if (null === $classNode) {
            return false;
        }
        $name = $classNode->getNamespacedName();

        if ((string) $name === (string) $className) {
            return true;
        }

        return false;
    }

    private function importClassInSameNamespace(Node $node, ClassName $className)
    {
        $namespace = '';
        if ($definition = $node->getNamespaceDefinition()) {
            $namespace = (string) $definition->getFirstChildNode(QualifiedName::class);
        }

        if ($className->namespace() == $namespace) {
            return true;
        }

        return false;
    }
}
