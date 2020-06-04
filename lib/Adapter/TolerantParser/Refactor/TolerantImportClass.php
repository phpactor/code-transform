<?php

namespace Phpactor\CodeTransform\Adapter\TolerantParser\Refactor;

use Microsoft\PhpParser\ClassLike;
use Microsoft\PhpParser\NamespacedNameInterface;
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
use Phpactor\TextDocument\TextEdit;
use Phpactor\TextDocument\TextEdits;

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

    public function importClass(SourceCode $source, int $offset, string $name, ?string $alias = null): TextEdits
    {
        return $this->importName('class', $source, $offset, $name, $alias);
    }

    public function importFunction(SourceCode $source, int $offset, string $name, ?string $alias = null): TextEdits
    {
        return $this->importName('function', $source, $offset, $name, $alias);
    }

    public function importName(string $type, SourceCode $source, int $offset, string $name, ?string $alias = null): TextEdits
    {
        $name = ClassName::fromString($name);
        $sourceNode = $this->parser->parseSourceFile($source);
        $node = $sourceNode->getDescendantNodeAtPosition($offset);

        if (!$node instanceof NamespacedNameInterface) {
            return TextEdits::none();
        }

        $this->checkIfAlreadyImported($type, $node, $name, $alias);

        $edits = $this->addImport($type, $source, $node, $name, $alias);

        if ($alias !== null) {
            $edits = $this->updateReferences($node, $name, $alias, $edits);
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

    private function checkIfAlreadyImported(string $type, Node $node, ClassName $className, ?string $alias = null)
    {
        $currentClass = $this->currentClass($node);
        $imports = $node->getImportTablesForCurrentScope()[$this->resolveImportTableOffset($type)];

        if (null === $alias && isset($imports[$className->short()])) {
            throw new ClassAlreadyImportedException($type, $className->short(), $imports[$className->short()]);
        }

        if (null === $alias && $currentClass && $currentClass->short() === $className->short()) {
            throw new ClassAlreadyImportedException($type, $className->short(), $currentClass->__toString());
        }

        if ($alias && isset($imports[$alias])) {
            throw new AliasAlreadyUsedException($type, $alias);
        }

        if ($this->currentClassIsSameAsImportClass($node, $className)) {
            throw new ClassIsCurrentClassException($type, $className->short());
        }

        if ($this->importClassInSameNamespace($node, $className)) {
            throw new ClassAlreadyInNamespaceException($type, $className->short());
        }
    }

    private function currentClassIsSameAsImportClass(Node $node, ClassName $className): bool
    {
        if (!$node instanceof ClassDeclaration) {
            return false;
        }

        if ((string) $node->getNamespacedName() === (string) $className) {
            return true;
        }

        return false;
    }


    private function addImport(string $type, SourceCode $source, Node $node, string $name, string $alias = null): TextEdits
    {
        $builder = SourceCodeBuilder::create();

        $this->addUse($type, $builder, $name, $alias);
        $prototype = $builder->build();

        return $this->updater->textEditsFor($prototype, Code::fromString((string) $source));
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


    private function updateReferences(Node $node, string $name, string $alias, TextEdits $edits): TextEdits
    {
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

    private function resolveImportTableOffset(string $type): int
    {
        return $type === 'function' ? 1 : 0;
    }

    private function addUse(string $type, SourceCodeBuilder $builder, string $name, ?string $alias): void
    {
        if ($type === 'function') {
            $builder->useFunction($name, $alias);
            return;
        }

        $builder->use($name, $alias);
    }
}
