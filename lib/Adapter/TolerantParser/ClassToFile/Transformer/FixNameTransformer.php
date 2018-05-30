<?php

namespace Phpactor\CodeTransform\Adapter\TolerantParser\ClassToFile\Transformer;

use Microsoft\PhpParser\ClassLike;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Microsoft\PhpParser\Node\Statement\InlineHtml;
use Microsoft\PhpParser\Node\Statement\InterfaceDeclaration;
use Microsoft\PhpParser\Node\Statement\NamespaceDefinition;
use Microsoft\PhpParser\Node\Statement\TraitDeclaration;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\TextEdit;
use Phpactor\ClassFileConverter\Domain\FilePath;
use Phpactor\ClassFileConverter\Domain\FileToClass;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Domain\Transformer;

class FixNameTransformer implements Transformer
{
    /**
     * @var FileToClass
     */
    private $fileToClass;

    /**
     * @var Parser
     */
    private $parser;

    public function __construct(FileToClass $fileToClass, Parser $parser = null)
    {
        $this->fileToClass = $fileToClass;
        $this->parser = $parser ?: new Parser();
    }

    public function transform(SourceCode $code): SourceCode
    {
        $candidates = $this->fileToClass->fileToClassCandidates(
            FilePath::fromString((string) $code->path())
        );

        $classFqn = $candidates->best();
        $className = $classFqn->name();
        $namespace = $classFqn->namespace();

        $rootNode = $this->parser->parseSourceFile((string) $code);
        $edits = $this->fixNamespace($rootNode, $namespace);
        //$edits = $this->fixClassName($rootNode, $className);

        return $code->withSource(TextEdit::applyEdits($edits, (string) $code));
    }

    private function fixClassName(SourceFileNode $rootNode, string $correctClassName): array
    {
        $classLike = $rootNode->getFirstDescendantNode(ClassLike::class);
        
        if (null === $classLike) {
            return [];
        }
        
        assert($classLike instanceof ClassDeclaration || $classLike instanceof InterfaceDeclaration || $classLike instanceof TraitDeclaration);
        
        $name = $classLike->name->getText($rootNode->getFileContents());
        if ($name === $correctClassName) {
            return [];
        }

        throw new \Exception('TODO: This');
    }

    private function fixNamespace(SourceFileNode $rootNode, $correctNamespace)
    {
        $namespaceDefinition = $rootNode->getFirstDescendantNode(NamespaceDefinition::class);
        $statement = sprintf('namespace %s;', $correctNamespace);

        if (null === $namespaceDefinition) {
            $scriptStart = $rootNode->getFirstDescendantNode(InlineHtml::class);
            return [ new TextEdit($scriptStart->getEndPosition(), 0, $statement) ];
        }

        return [];
    }

}
