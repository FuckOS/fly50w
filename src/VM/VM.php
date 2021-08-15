<?php

namespace Fly50w\VM;

use Exception;
use Fly50w\Exceptions\InvalidASTException;
use Fly50w\Parser\AST\AssignNode;
use Fly50w\Parser\AST\BraceExpressionNode;
use Fly50w\Parser\AST\BreakNode;
use Fly50w\Parser\AST\FunctionCallNode;
use Fly50w\Parser\AST\FunctionNode;
use Fly50w\Parser\AST\LiteralNode;
use Fly50w\Parser\AST\Node;
use Fly50w\Parser\AST\OperatorNode;
use Fly50w\Parser\AST\ReturnNode;
use Fly50w\Parser\AST\RootNode;
use Fly50w\Parser\AST\Scope;
use Fly50w\Parser\AST\StatementNode;
use Fly50w\Parser\AST\VariableNode;
use Fly50w\VM\Internal\BreakFlag;
use Fly50w\VM\Internal\ReturnFlag;
use RuntimeException;

class VM
{
    public array $states = [];

    public function __construct()
    {
        $this->states['print'] = function (array $args, VM $vm) {
            foreach ($args as $arg) {
                if (is_string($arg)) {
                    echo $arg;
                } else {
                    var_dump($arg);
                }
            }
            return $args[0];
        };
    }

    public function execute(RootNode $ast): mixed
    {
        foreach ($ast->getChildren() as $child) {
            $rslt = $this->runNode($child);
            if ($rslt instanceof ReturnFlag) {
                return $rslt->data;
            }
        }
        return null;
    }

    public function runNode(Node $node): mixed
    {
        if ($node instanceof LiteralNode) {
            return $node->getValue();
        }
        if ($node instanceof VariableNode) {
            return $this->getVariable($node->getName(), $node->getScope());
        }
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
            if ($node->getChildren() instanceof BreakNode) {
                return new BreakFlag();
            }
            if ($node->getTopChild() instanceof ReturnNode) {
                return new ReturnFlag(
                    $this->runNode($node->getTopChild()->getTopChild())
                );
            }
            if ($node->getTopChild() != null) // FIXME: ok
                $this->runNode($node->getTopChild());
            return null;
        }
        if ($node instanceof AssignNode) {
            if (
                !($node->getBottomChild() instanceof VariableNode) ||
                $node->countChildren() != 2
            ) {
                throw new InvalidASTException('Excepted VariableNode child for AssignNode');
            }
            $name = $node->getChildren()[0]->getName();
            if ($node->isLet()) {
                $name = $node->getScope() . $name;
            } else {
                $scope = $node->getScope();
                while (!array_key_exists($scope . $name, $this->states)) {
                    if ($scope->isRoot()) {
                        throw new RuntimeException('Undefined variable: ' . $name);
                    }
                    $scope = $scope->upperScope();
                }
                $name = $scope . $name;
            }
            $this->states[$name] = $this->runNode($node->getChildren()[1]);
            return $this->states[$name];
        }
        if ($node instanceof FunctionNode) {
            return function (array $args, VM $vm) use ($node) {
                $params = $node->getParams();
                if (count($args) != count($params)) {
                    throw new RuntimeException('Wrong argument number');
                }
                $params = array_map(fn ($v) => $node->getScope() . $v, $params);
                foreach ($params as $k => $v) {
                    $vm->states[$v] = $args[$k];
                }
                foreach ($node->getChildren() as $child) {
                    $rslt = $vm->runNode($child);
                    if ($rslt instanceof ReturnFlag) {
                        return $rslt->data;
                    }
                }
                foreach ($params as $k => $v) {
                    unset($vm->states[$v]);
                }
            };
        }
        if ($node instanceof FunctionCallNode) {
            $func = $this->getVariable($node->getFunction(), $node->getScope());
            if (!is_callable($func)) {
                throw new Exception("Attempting to call $func which is not a function");
            }
            $args = [];
            if (!($node->getTopChild() instanceof BraceExpressionNode)) {
                throw new Exception('Invalid AST in FunctionCallNode');
            }
            foreach ($node->getTopChild()->getChildren() as $child) {
                $args[] = $this->runNode($child);
            }
            return $func($args, $this);
        }
        return null;
    }

    public function getVariable(string $name, ?Scope $nodeScope = null): mixed
    {
        if ($nodeScope === null) {
            return array_key_exists($name, $this->states) ? $this->states[$name] : null;
        }
        $scope = $nodeScope;
        while (!array_key_exists($scope . $name, $this->states)) {
            if ($scope->isRoot()) {
                throw new RuntimeException('Undefined variable: ' . $name);
            }
            $scope = $scope->upperScope();
        }
        return $this->states[$scope . $name];
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