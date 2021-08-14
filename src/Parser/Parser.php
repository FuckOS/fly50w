<?php

namespace Fly50w\Parser;

use Fly50w\Exceptions\UnmatchedBracketsException;
use Fly50w\Lexer\Token;
use Fly50w\Parser\AST\AssignNode;
use Fly50w\Parser\AST\FunctionNode;
use Fly50w\Parser\AST\LiteralNode;
use Fly50w\Parser\AST\Node;
use Fly50w\Parser\AST\RootNode;
use Fly50w\Parser\AST\StatementNode;
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
        $curr = new StatementNode();
        $root->addChild($curr);
        $brackets = new \SplStack();
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
                case Token::T_KEYWORD:
                    switch ($token->value) {
                        case 'fn':
                            $fn = new FunctionNode();
                            $curr->addChild($fn);
                            $curr = $fn;
                            $curr->_in_param = true;
                            break;
                    }
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
                        case ';':
                            $parent = $curr->getParent();
                            $curr = new StatementNode();
                            $parent->addChild($curr);
                            break;
                        case '(':
                            $brackets->push('(');
                            if ($curr instanceof FunctionNode && $curr->_in_param) {
                                break;
                            }
                            break;
                        case ')':
                            $last = $brackets->pop();
                            if ($last != '(') {
                                throw new UnmatchedBracketsException();
                            }
                            if ($curr instanceof FunctionNode && $curr->_in_param) {
                                $curr->_in_param = false;
                                break;
                            }
                            // TODO: finish this
                            break;
                        case '{':
                            $brackets->push('{');
                            if ($curr instanceof FunctionNode) {
                                if ($curr->_in_param) {
                                    $curr->_in_param = false;
                                }
                                // TODO: finish this
                            }
                            // TODO: finish this
                            break;
                        case ',':
                    }
                    break;
            }
        }
        return $root;
    }
}
