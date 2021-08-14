<?php

namespace Fly50w\Parser;

use Fly50w\Lexer\Token;
use Fly50w\Parser\AST\AssignNode;
use Fly50w\Parser\AST\LiteralNode;
use Fly50w\Parser\AST\Node;
use Fly50w\Parser\AST\RootNode;
use Fly50w\Parser\AST\VariableNode;

class Parser
{
    public function __construct()
    {
    }

    /**
     * Parse the normalized tokens
     *
     * @param Token[] $code
     * @return Node
     */
    public function parse(
        array $tokens,
        string $filename = 'IN_MEMORY_CODE',
        ?Node $parent = null
    ): Node {
        $root = $parent ?? new RootNode($filename);
        $curr = $root;
        foreach ($tokens as $offset => $token) {
            if ($curr->isFull()) {
                $curr = $curr->getParent();
            }
            switch ($token->type) {
                case Token::T_SCALAR:
                    $curr->addChild(new LiteralNode(
                        value: $token->scalar->value,
                        type: $token->scalar->type
                    ));
                    break;
                case Token::T_IDENTIFY:
                    $curr->addChild(new VariableNode($token->value));
                    break;
                case Token::T_SYMBOL:
                    switch ($token->value) {
                        case '=':
                            $prev = $curr->popChild();
                            $an = new AssignNode();
                            $curr->addChild($an);
                            $curr = $an;
                            $curr->addChild($prev);
                            break;
                    }
                    break;
            }
        }
        return $root;
    }
}
