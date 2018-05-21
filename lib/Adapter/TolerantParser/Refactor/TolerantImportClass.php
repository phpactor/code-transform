<?php

namespace Phpactor\CodeTransform\Adapter\TolerantParser\Refactor;

use Phpactor\CodeTransform\Domain\Refactor\ImportClass;
use Microsoft\PhpParser\Parser;
use Phpactor\CodeTransform\Domain\ClassFinder\ClassFinder;
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
use Phpactor\XmlQuery\Bridge\TolerantParser\TolerantSourceLoader;
use Phpactor\XmlQuery\NodeList;
use Phpactor\XmlQuery\SourceLoader;
use Phpactor\XmlQuery\Node as XmlNode;

class TolerantImportClass implements ImportClass
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var SourceLoader
     */
    private $loader;

    public function __construct(SourceLoader $loader = null, Parser $parser = null)
    {
        $this->parser = $parser ?: new Parser();;
        $this->loader = $loader ?: new TolerantSourceLoader([], $this->parser);
    }

    public function importClass(SourceCode $source, int $offset, string $name, string $alias = null): SourceCode
    {
        $name = ClassName::fromString($name);
        $sourceNode = $this->parser->parseSourceFile($source);
        $node = $sourceNode->getDescendantNodeAtPosition($offset);

        $this->checkIfAlreadyImported($node, $name, $alias);

        return $this->addImport($source, $name, $alias);
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
            throw new ClassIsCurrentClassException($className->short(), (string) $className);
        }

        if ($this->importClassInSameNamespace($node, $className)) {
            throw new ClassAlreadyInNamespaceException($className->short(), (string) $className);
        }
    }

    private function currentClassIsSameAsImportClass(Node $node, ClassName $className): bool
    {
        if ($node instanceof ClassDeclaration) {
            return true;
        }

        /** @var ClassDeclaration $classNode */
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
        if ($namespace = $node->getNamespaceDefinition()) {
            $namespace = (string) $node->getNamespaceDefinition()->getFirstChildNode(QualifiedName::class);
        }

        if ($className->namespace() == $namespace) {
            return true;
        }

        return false;
    }

    private function addImport(SourceCode $source, string $name, string $alias = null): SourceCode
    {
        $node = $this->loader->loadSource($source->__toString());

        $existingNames = $node->find('//NamespaceUseDeclaration//Token[@kind="Name"]');
        $statement =  'use ' . $name;

        if ($alias) {
            $statement .= ' as ' . $alias;
        }
        $statement = $statement . ';';

        if ($existingNames->count()) {
            $this->updateExistingNames($existingNames, $name, $statement);
            return $source->withSource($node->text());
        }

        $namespaceDefinitions = $node->find('//NamespaceDefinition');

        if ($namespaceDefinitions->count()) {
            $namespaceDefinitions->first()->after($node->createText(PHP_EOL . PHP_EOL . $statement));

            return $source->withSource($node->text());
        }

        $node->find('//InlineHtml|//DeclareStatement')->last()->after($node->createText(PHP_EOL . $statement));

        return $source->withSource($node->text());
    }

    /**
     * @param NodeList<XmlNode> $existingNames
     * @param string $name
     * @param string $statement
     */
    private function updateExistingNames(NodeList $existingNames, string $name, string $statement)
    {
        foreach ($existingNames as $existingName) {
            $cmp = strcmp($existingName->text(), $name);

            if ($cmp > 0) {
                $node = $existingName->find('ancestor::NamespaceUseDeclaration//Token[@kind="UseKeyword"]')->first();
                $node->before(
                    $node->createText('use ' . $name . ';' . PHP_EOL)
                );

                return;
            }
        }
    }
}
