<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Helper;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\QualifiedName;
use Phpactor\CodeTransform\Domain\NameWithByteOffset;
use Phpactor\CodeTransform\Domain\NameWithByteOffsets;
use Phpactor\Name\QualifiedName as PhpactorQualifiedName;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Parser;
use Phpactor\CodeTransform\Domain\Helper\UnresolvableClassNameFinder;
use Phpactor\TextDocument\ByteOffset;
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
        $nameText = (string)$name->getResolvedName();

        // If node cannot provide a "resolved" name then this is not a valid
        // candidate (e.g. it may be part of a namespace statement) and we can
        // ignore it.
        if (!$nameText || in_array($nameText, ['self', 'static'])) {
            return $unresolvedNames;
        }

        try {
            $class = $this->reflector->reflectClassLike($nameText);
        } catch (NotFound $notFound) {
            $unresolvedNames[] = new NameWithByteOffset(
                PhpactorQualifiedName::fromString($nameText),
                ByteOffset::fromInt($name->getStart())
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
