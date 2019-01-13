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
use Phpactor\ClassFileConverter\Domain\FilePath;
use Phpactor\ClassFileConverter\Domain\FileToClass;
use Phpactor\CodeBuilder\Adapter\TolerantParser\TextEdit;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Domain\Transformer;
use RuntimeException;

class ClassNameFixerTransformer implements Transformer
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
        if (!$code->path()) {
            throw new RuntimeException('Source code has no path associated with it');
        }

        $candidates = $this->fileToClass->fileToClassCandidates(
            FilePath::fromString((string) $code->path())
        );

        $classFqn = $candidates->best();
        $correctClassName = $classFqn->name();
        $correctNamespace = $classFqn->namespace();

        $rootNode = $this->parser->parseSourceFile((string) $code);
        $edits = [];

        if ($textEdit = $this->fixNamespace($rootNode, $correctNamespace)) {
            $edits[] = $textEdit;
        }

        if ($textEdit = $this->fixClassName($rootNode, $correctClassName)) {
            $edits[] = $textEdit;
        }

        return $code->withSource(TextEdit::applyEdits($edits, (string) $code));
    }

    /**
     * @return TextEdit|null
     */
    private function fixClassName(SourceFileNode $rootNode, string $correctClassName): ?TextEdit
    {
        $classLike = $rootNode->getFirstDescendantNode(ClassLike::class);
        
        if (null === $classLike) {
            return null;
        }
        
        assert($classLike instanceof ClassDeclaration || $classLike instanceof InterfaceDeclaration || $classLike instanceof TraitDeclaration);
        
        $name = $classLike->name->getText($rootNode->getFileContents());

        if (!is_string($name) || $name === $correctClassName) {
            return null;
        }

        return new TextEdit($classLike->name->start, strlen($name), $correctClassName);
    }

    /**
     * @return TextEdit|null
     */
    private function fixNamespace(SourceFileNode $rootNode, $correctNamespace)
    {
        $namespaceDefinition = $rootNode->getFirstDescendantNode(NamespaceDefinition::class);
        $statement = sprintf('namespace %s;', $correctNamespace);

        if ($correctNamespace && null === $namespaceDefinition) {
            $scriptStart = $rootNode->getFirstDescendantNode(InlineHtml::class);
            $scriptStart = $scriptStart ? $scriptStart->getEndPosition() : 0;

            $statement = PHP_EOL . $statement . PHP_EOL;

            if (0 === $scriptStart) {
                $statement = '<?php' . PHP_EOL . $statement;
            }


            return new TextEdit($scriptStart, 0, $statement);
        }

        if (null === $namespaceDefinition) {
            return null;
        }

        return new TextEdit(
            $namespaceDefinition->getStart(),
            $namespaceDefinition->getEndPosition() - $namespaceDefinition->getStart(),
            $statement
        );
    }
}
