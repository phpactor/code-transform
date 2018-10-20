<?php

namespace Phpactor\CodeTransform\Adapter\TolerantParser\Refactor;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\ClassConstDeclaration;
use Microsoft\PhpParser\Node\MethodDeclaration;
use Microsoft\PhpParser\Node\PropertyDeclaration;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\TextEdit;
use Microsoft\PhpParser\Token;
use Microsoft\PhpParser\TokenKind;
use Phpactor\CodeTransform\Domain\SourceCode;

class TolerantChangeVisiblity
{
    /**
     * @var Parser
     */
    private $parser;

    public function __construct(Parser $parser = null)
    {
        $this->parser = $parser ?: new Parser();
    }

    public function changeVisiblity(SourceCode $source, int $offset): SourceCode
    {
        $node = $this->parser->parseSourceFile((string) $source);
        $node = $node->getDescendantNodeAtPosition($offset);

        if (!(
            $node instanceof MethodDeclaration ||
            $node instanceof PropertyDeclaration ||
            $node instanceof ClassConstDeclaration
        )) {
            return $source;
        }

        $textEdit = $this->resolveNewVisiblityTextEdit($node);


        return $source->withSource(TextEdit::applyEdits([
            $textEdit
        ], (string) $source));
    }

    /**
     * @param MethodDeclaration|PropertyDeclaration|ClassConsDeclaration $node
     */
    private function resolveNewVisiblityTextEdit(Node $node): TextEdit
    {
        foreach ($node->modifiers as $modifier) {
            if ($modifier->kind === TokenKind::PublicKeyword) {
                return $this->visiblityTextEdit($modifier, 'protected');
            }

            if ($modifier->kind === TokenKind::ProtectedKeyword) {
                return $this->visiblityTextEdit($modifier, 'private');
            }

            if ($modifier->kind === TokenKind::PrivateKeyword) {
                return $this->visiblityTextEdit($modifier, 'public');
            }
        }

        return $this->visiblityTextEdit($modifier, 'private');
    }

    private function visiblityTextEdit(Token $modifier, $newVisiblity): TextEdit
    {
        return new TextEdit($modifier->getStartPosition(), $modifier->getWidth(), $newVisiblity);
    }
}
