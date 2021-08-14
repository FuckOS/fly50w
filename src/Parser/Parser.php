<?php

namespace Fly50w\Parser;

use Fly50w\Lexer\Token;
use Fly50w\Parser\AST\LiteralNode;
use Fly50w\Parser\AST\Node;
use Fly50w\Parser\AST\RootNode;

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
            switch ($token->type) {
                case Token::T_SCALAR:
                    $curr->addChild(new LiteralNode(
                        value: $token->scalar->value,
                        type: $token->scalar->type
                    ));
                    break;
            }
        }
        return $root;
    }
}
