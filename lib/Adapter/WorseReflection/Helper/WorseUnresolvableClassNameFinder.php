<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Helper;

use Generator;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\QualifiedName;
use Phpactor\Name\QualifiedName as PhpactorQualifiedName;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Parser;
use Phpactor\CodeTransform\Domain\Helper\UnresolvableClassNameFinder;
use Phpactor\Name\Names;
use Phpactor\TextDocument\TextDocument;
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Core\Reflector\ClassReflector;

class WorseUnresolvableClassNameFinder implements UnresolvableClassNameFinder
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var ClassReflector
     */
    private $reflector;

    public function __construct(ClassReflector $reflector, Parser $parser = null)
    {
        $this->parser = $parser ?: new Parser();
        $this->reflector = $reflector;
    }

    public function find(TextDocument $sourceCode): Names
    {
        $rootNode = $this->parser->parseSourceFile($sourceCode);
        $names = $this->findNameNodes($rootNode);
        $names = $this->filterResolvedNames($names);

        return Names::fromNames($names);
    }

    private function findNameNodes(SourceFileNode $rootNode): array
    {
        return array_filter(iterator_to_array($rootNode->getDescendantNodes()), function (Node $node) {
            return $node instanceof QualifiedName;
        });
    }

    private function filterResolvedNames(array $names): array
    {
        $unresolvedNames = [];
        foreach ($names as $name) {
            $unresolvedNames = $this->appendUnresolvedName($name, $unresolvedNames);
        }

        return $unresolvedNames;
    }

    private function appendUnresolvedName(QualifiedName $name, array $unresolvedNames)
    {
        $nameText = $name->getNamespacedName()->getFullyQualifiedNameText();

        try {
            $class = $this->reflector->reflectClass($nameText);
        } catch (NotFound $notFound) {
            $unresolvedNames[] = PhpactorQualifiedName::fromString($nameText);
        }

        return $unresolvedNames;
    }

}
