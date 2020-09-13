<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Helper;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\ClassBaseClause;
use Microsoft\PhpParser\Node\DelimitedList\QualifiedNameList;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\ObjectCreationExpression;
use Microsoft\PhpParser\Node\Expression\ScopedPropertyAccessExpression;
use Microsoft\PhpParser\Node\Parameter;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node\Statement\FunctionDeclaration;
use Phpactor\CodeTransform\Domain\NameWithByteOffset;
use Phpactor\CodeTransform\Domain\NameWithByteOffsets;
use Phpactor\Name\QualifiedName as PhpactorQualifiedName;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Parser;
use Phpactor\CodeTransform\Domain\Helper\UnresolvableClassNameFinder;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Reflector;

class WorseUnresolvableClassNameFinder implements UnresolvableClassNameFinder
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Reflector
     */
    private $reflector;

    public function __construct(Reflector $reflector, Parser $parser = null)
    {
        $this->parser = $parser ?: new Parser();
        $this->reflector = $reflector;
    }

    public function find(TextDocument $sourceCode): NameWithByteOffsets
    {
        $rootNode = $this->parser->parseSourceFile($sourceCode);
        $names = $this->findNameNodes($rootNode);
        $names = $this->filterResolvedNames($names);

        return new NameWithByteOffsets(...$names);
    }

    private function findNameNodes(SourceFileNode $rootNode): array
    {
        return array_filter($this->descendants($rootNode), function (Node $node) {
            if (!$node instanceof QualifiedName) {
                return false;
            }

            return true;
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

    private function appendUnresolvedName(QualifiedName $name, array $unresolvedNames): array
    {
        $resolvedName = (string)$name->getResolvedName();

        // Parser returns "NULL" for unqualified namespaced function / constant
        // names, but will return the FQN for references...
        if (!$resolvedName && $name->parent instanceof CallExpression) {
            return $this->appendUnresolvedFunctionName($name->getNamespacedName()->__toString(), $unresolvedNames, $name);
        }

        if (in_array($resolvedName, ['self', 'static', 'parent'])) {
            return $unresolvedNames;
        }

        // if the tolerant parser did not provide the resolved name (because of
        // bug) then use the namespaced name.
        if (!$resolvedName) {
            $resolvedName = $name->getNamespacedName();
        }

        // Function names in global namespace have a "resolved name"
        // (inconsistent parser behavior)
        if ($name->parent instanceof CallExpression) {
            return $this->appendUnresolvedFunctionName($name->getResolvedName() ?? $name->getText(), $unresolvedNames, $name);
        }

        if (
            !$name->parent instanceof ClassBaseClause &&
            !$name->parent instanceof QualifiedNameList &&
            !$name->parent instanceof ObjectCreationExpression &&
            !$name->parent instanceof ScopedPropertyAccessExpression &&
            !$name->parent instanceof FunctionDeclaration &&
            !$name->parent instanceof Parameter
        ) {
            return $unresolvedNames;
        }

        return $this->appendUnresolvedClassName($resolvedName, $unresolvedNames, $name);
    }

    private function appendUnresolvedClassName(string $nameText, array $unresolvedNames, QualifiedName $name): array
    {
        try {
            $class = $this->reflector->sourceCodeForClassLike($nameText);
        } catch (NotFound $notFound) {
            $unresolvedNames[] = new NameWithByteOffset(
                PhpactorQualifiedName::fromString($nameText),
                ByteOffset::fromInt($name->getStart()),
                NameWithByteOffset::TYPE_CLASS
            );
        }
        
        return $unresolvedNames;
    }

    private function appendUnresolvedFunctionName(string $nameText, array $unresolvedNames, QualifiedName $name): array
    {
        try {
            $this->reflector->sourceCodeForFunction($nameText);
        } catch (NotFound $notFound) {
            $unresolvedNames[] = new NameWithByteOffset(
                PhpactorQualifiedName::fromString($nameText),
                ByteOffset::fromInt($name->getStart()),
                NameWithByteOffset::TYPE_FUNCTION
            );
        }
        
        return $unresolvedNames;
    }

    private function descendants(Node $node): array
    {
        $descendants = [];
        foreach ($node->getChildNodes() as $childNode) {
            if (!$childNode instanceof Node) {
                continue;
            }

            $descendants[] = $childNode;
            $descendants = array_merge($descendants, $this->descendants($childNode));
        }

        return $descendants;
    }
}
