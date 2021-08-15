<?php

namespace Fly50w\VM;

use Fly50w\Exceptions\InvalidASTException;
use Fly50w\Parser\AST\BraceExpressionNode;
use Fly50w\Parser\AST\Node;
use Fly50w\Parser\AST\OperatorNode;
use Fly50w\Parser\AST\ReturnNode;
use Fly50w\Parser\AST\RootNode;
use Fly50w\Parser\AST\StatementNode;

class VM
{
    protected array $states = [];

    public function __construct()
    {
    }

    public function execute(RootNode $code): mixed
    {
    }

    public function runNode(Node $node): mixed
    {
        if ($node instanceof BraceExpressionNode) {
            if (count($node->getChildren()) > 1) {
                throw new InvalidASTException('Excepted 0~1 child for BraceExpressionNode');
            }
            return count($node->children) == 1 ? $this->runNode($node->children[0]) : null;
        }
        if ($node instanceof OperatorNode) {
            return $this->calculateOperator($node);
        }
        if ($node instanceof StatementNode) {
            if ($node->getTopChild() instanceof ReturnNode) {
                return $this->runNode($node->getTopChild()->getTopChild());
            }
            $this->runNode($node->getTopChild());
            return null;
        }
    }

    protected function calculateOperator(OperatorNode $node): mixed
    {
        if (count($node->getChildren()) != 2) {
            throw new InvalidASTException('Excepted 2 child for OperatorNode');
        }
        $a = $this->runNode($node->getChildren()[0]);
        $b = $this->runNode($node->getChildren()[1]);
        switch ($node->getOperator()) {
            case '+':
                return $a + $b;
            case '-':
                return $a - $b;
            case '*':
                return $a * $b;
            case '/':
                return $a / $b;
            case '%':
                return $a % $b;
            default:
                throw new InvalidASTException('Unknown operator');
        }
    }
}
