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
use Phpactor\CodeTransform\Domain\Refactor\ChangeVisiblity;
use Phpactor\CodeTransform\Domain\SourceCode;

class TolerantChangeVisiblity implements ChangeVisiblity
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

        $node = $this->resolveMemberNode($node);

        if (null === $node) {
            return $source;
        }

        $textEdit = $this->resolveNewVisiblityTextEdit($node);

        if (null === $textEdit) {
            return $source;
        }

        return $source->withSource(TextEdit::applyEdits([
            $textEdit
        ], (string) $source));
    }

    /**
     * @param MethodDeclaration|PropertyDeclaration|ClassConstDeclaration $node
     */
    private function resolveNewVisiblityTextEdit(Node $node): ?TextEdit
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
    }

    private function visiblityTextEdit(Token $modifier, $newVisiblity): TextEdit
    {
        return new TextEdit($modifier->getStartPosition(), $modifier->getWidth(), $newVisiblity);
    }

    private function resolveMemberNode(Node $node)
    {
        if (!(
            $node instanceof MethodDeclaration ||
            $node instanceof PropertyDeclaration ||
            $node instanceof ClassConstDeclaration
        )) {
            $node = $node->getFirstAncestor(
                MethodDeclaration::class,
                PropertyDeclaration::class,
                ClassConstDeclaration::class
            );
        }
        return $node;
    }
}
