<?php

namespace Phpactor\CodeTransform\Adapter\TolerantParser\Refactor;

use Microsoft\PhpParser\ClassLike;
use Microsoft\PhpParser\NamespacedNameInterface;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\NameImport;
use Phpactor\CodeTransform\Domain\Refactor\ImportName;
use Microsoft\PhpParser\Parser;
use Phpactor\CodeTransform\Domain\SourceCode;
use Microsoft\PhpParser\Node\QualifiedName;
use Phpactor\CodeTransform\Domain\Exception\TransformException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\NameAlreadyImportedException;
use Phpactor\CodeTransform\Domain\ClassName;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Microsoft\PhpParser\Node;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\AliasAlreadyUsedException;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\ClassIsCurrentClassException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\NameAlreadyInNamespaceException;
use Phpactor\Name\FullyQualifiedName;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextEdit;
use Phpactor\TextDocument\TextEdits;

class TolerantImportName implements ImportName
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

    public function importName(SourceCode $source, ByteOffset $offset, NameImport $nameImport): TextEdits
    {
        $sourceNode = $this->parser->parseSourceFile($source);
        $node = $sourceNode->getDescendantNodeAtPosition($offset->toInt());

        if (!$node instanceof NamespacedNameInterface) {
            return TextEdits::none();
        }

        $this->checkIfAlreadyImported($node, $nameImport);

        $edits = $this->addImport($source, $node, $nameImport);

        if ($nameImport->alias() !== null) {
            $edits = $this->updateReferences($node, $nameImport, $edits);
        }

        return $edits;
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

    private function checkIfAlreadyImported(Node $node, NameImport $nameImport)
    {
        $currentClass = $this->currentClass($node);
        $imports = $node->getImportTablesForCurrentScope()[$this->resolveImportTableOffset($nameImport)];

        if (null === $nameImport->alias() && isset($imports[$nameImport->name()->head()->__toString()])) {
            throw new NameAlreadyImportedException($nameImport, $imports[$nameImport->name()->head()->__toString()]);
        }

        if (null === $nameImport->alias() && $currentClass && $currentClass->short() === $nameImport->name()->head()->__toString()) {
            throw new NameAlreadyImportedException($nameImport, $currentClass->__toString());
        }

        if ($nameImport->alias() && isset($imports[$nameImport->alias()])) {
            throw new AliasAlreadyUsedException($nameImport);
        }

        if ($nameImport->isClass() && $this->currentClassIsSameAsImportClass($node, $nameImport->name())) {
            throw new ClassIsCurrentClassException($nameImport);
        }

        if ($this->importClassInSameNamespace($node, $nameImport->name())) {
            throw new NameAlreadyInNamespaceException($nameImport);
        }
    }

    private function currentClassIsSameAsImportClass(Node $node, FullyQualifiedName $className): bool
    {
        if (!$node instanceof ClassDeclaration) {
            return false;
        }

        if ((string) $node->getNamespacedName() === (string) $className) {
            return true;
        }

        return false;
    }


    private function addImport(SourceCode $source, Node $node, NameImport $nameImport): TextEdits
    {
        $builder = SourceCodeBuilder::create();

        $this->addUse($builder, $nameImport);
        $prototype = $builder->build();

        return $this->updater->textEditsFor($prototype, Code::fromString((string) $source));
    }

    private function importClassInSameNamespace(Node $node, FullyQualifiedName $className)
    {
        $namespace = '';
        if ($definition = $node->getNamespaceDefinition()) {
            $namespace = (string) $definition->getFirstChildNode(QualifiedName::class);
        }

        if ($className->tail()->__toString() == $namespace) {
            return true;
        }

        return false;
    }


    private function updateReferences(Node $node, NameImport $nameImport, TextEdits $edits): TextEdits
    {
        $alias = $nameImport->alias();

        if (is_null($alias)) {
            return $edits;
        }

        return $edits->add(TextEdit::create(
            $node->getStart(),
            $node->getEndPosition() - $node->getStart(),
            $alias
        ));
    }

    private function currentClass(Node $node): ?ClassName
    {
        $classDeclaration = $node->getFirstAncestor(ClassLike::class);

        if (!$classDeclaration instanceof NamespacedNameInterface) {
            return null;
        }


        $name = (string)$classDeclaration->getNamespacedName();

        if (!$name) {
            return null;
        }

        return ClassName::fromString($name);
    }

    private function resolveImportTableOffset(NameImport $nameImport): int
    {
        return $nameImport->isFunction() ? 1 : 0;
    }

    private function addUse(SourceCodeBuilder $builder, NameImport $nameImport): void
    {
        if ($nameImport->isFunction()) {
            $builder->useFunction($nameImport->name()->__toString(), $nameImport->alias());
            return;
        }

        $builder->use($nameImport->name()->__toString(), $nameImport->alias());
    }
}
