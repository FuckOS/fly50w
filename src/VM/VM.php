<?php

namespace Fly50w\VM;

use DivisionByZeroError;
use Error;
use Exception;
use Fly50w\Exceptions\InvalidASTException;
use Fly50w\Parser\AST\ArgumentNode;
use Fly50w\Parser\AST\AssignNode;
use Fly50w\Parser\AST\BraceExpressionNode;
use Fly50w\Parser\AST\BreakNode;
use Fly50w\Parser\AST\ForNode;
use Fly50w\Parser\AST\FunctionCallNode;
use Fly50w\Parser\AST\FunctionNode;
use Fly50w\Parser\AST\LiteralNode;
use Fly50w\Parser\AST\Node;
use Fly50w\Parser\AST\OperatorNode;
use Fly50w\Parser\AST\ReturnNode;
use Fly50w\Parser\AST\RootNode;
use Fly50w\Parser\AST\Scope;
use Fly50w\Parser\AST\StatementNode;
use Fly50w\Parser\AST\TryNode;
use Fly50w\Parser\AST\VariableNode;
use Fly50w\VM\Internal\BreakFlag;
use Fly50w\VM\Internal\ReturnFlag;
use Fly50w\VM\Types\Label;
use Fly50w\Exceptions\RuntimeException;
use Fly50w\Parser\AST\ExceptNode;
use Fly50w\Parser\AST\LabelNode;
use Fly50w\Parser\AST\ThrowNode;
use Fly50w\VM\Internal\PurgeResultFlag;
use Fly50w\VM\Internal\ThrowFlag;
use TypeError;

class VM
{
    public array $states = [
        'INF' => INF
    ];

    public ?Label $currentError = null;

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
        $this->states['assert'] = function (array $args, VM $vm) {
            foreach ($args as $arg) {
                if (!$arg) {
                    return $vm->throwError('assertError');
                }
            }
            return true;
        };
    }

    public function execute(RootNode $ast): mixed
    {
        $last_rslt = null;
        foreach ($ast->getChildren() as $child) {
            $rslt = $this->runNode($child);
            if ($rslt instanceof ReturnFlag) {
                return $rslt->data;
            }
            if ($rslt !== null) $last_rslt = $rslt;
        }
        return $last_rslt;
    }

    public function runNode(Node $node): mixed
    {
        if ($node instanceof LiteralNode) {
            return $node->getValue();
        }
        if ($node instanceof LabelNode) {
            return new Label($node->getName());
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
            try {
                $rslt = $this->calculateOperator($node);
            } catch (Error $e) {
                if ($e instanceof DivisionByZeroError) {
                    return $this->throwError('divisionByZero');
                } else {
                    throw $e;
                }
            }
            return $rslt;
        }
        if ($node instanceof StatementNode) {
            if ($this->currentError != null) {
                throw new RuntimeException("Uncaught error {$this->currentError->getName()}");
            }
            if ($node->getTopChild() instanceof BreakNode) {
                return new BreakFlag();
            }
            if ($node->getTopChild() instanceof ReturnNode) {
                if ($node->getTopChild()->countChildren() === 0) {
                    return new ReturnFlag();
                }
                return new ReturnFlag(
                    $this->runNode($node->getTopChild()->getTopChild())
                );
            }
            $last_rslt = null;
            foreach ($node->getChildren() as $child) {
                $rslt = $this->runNode($child);
                $last_rslt = $rslt ?? $last_rslt;
                if ($rslt instanceof PurgeResultFlag) {
                    $last_rslt = null;
                }
            }
            return $last_rslt;
        }
        if ($node instanceof ThrowNode) {
            if ($node->countChildren() != 1) {
                throw new RuntimeException("Must throw something out");
            }
            $err = $this->runNode($node->getTopChild());
            if (!($err instanceof Label)) {
                throw new RuntimeException('You can only throw a Label');
            }
            $this->currentError = $err;
            return new ThrowFlag($err);
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
                $last_rslt = null;
                foreach ($node->getChildren() as $child) {
                    $rslt = $vm->runNode($child);
                    if ($rslt instanceof ReturnFlag) {
                        return $rslt->data;
                    }
                    if ($rslt instanceof ThrowFlag) {
                        return $rslt;
                    }
                    $last_rslt = $rslt ?? $last_rslt;
                }
                foreach ($params as $k => $v) {
                    unset($vm->states[$v]);
                }
                return $last_rslt;
            };
        }
        if ($node instanceof FunctionCallNode) {
            $func = $this->getVariable($node->getFunction(), $node->getScope());
            if (!is_callable($func)) {
                throw new Exception("Attempting to call $func which is not a function");
            }
            $args = [];
            if (!($node->getTopChild() instanceof ArgumentNode)) {
                throw new Exception('Invalid AST in FunctionCallNode');
            }
            foreach ($node->getTopChild()->getChildren() as $child) {
                $args[] = $this->runNode($child);
            }
            return $func($args, $this);
        }
        if ($node instanceof ForNode) {
            $last_rslt = null;
            while (true) {
                foreach ($node->getChildren() as $child) {
                    $rslt = $this->runNode($child);
                    if ($rslt instanceof BreakFlag) {
                        return $last_rslt;
                    }
                    if ($rslt instanceof ReturnFlag) {
                        return $rslt;
                    }
                    if ($rslt instanceof ThrowFlag) {
                        return $rslt;
                    }
                    $last_rslt = $rslt ?? $last_rslt;
                }
            }
        }
        if ($node instanceof TryNode) {
            $last_rslt = null;
            foreach ($node->getChildren() as $child) {
                $rslt = $this->runNode($child);
                if ($rslt instanceof ReturnFlag) {
                    return $rslt;
                }
                if ($rslt instanceof ThrowFlag) {
                    return $rslt;
                }
                $last_rslt = $rslt ?? $last_rslt;
            }
            return $last_rslt;
        }
        if ($node instanceof ExceptNode) {
            if ($this->currentError === null) {
                return null;
            }
            if (in_array(
                $this->currentError->getName(),
                $node->getLabels()
            )) {
                $this->currentError = null;
                $last_rslt = new PurgeResultFlag;
                foreach ($node->getChildren() as $child) {
                    $rslt = $this->runNode($child);
                    if ($rslt instanceof ReturnFlag) {
                        return $rslt;
                    }
                    if ($rslt instanceof ThrowFlag) {
                        return $rslt;
                    }
                    $last_rslt = $rslt ?? $last_rslt;
                }
                return $last_rslt;
            }
            return null;
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

    public function throwError(string $label): ThrowFlag
    {
        $this->currentError = new Label($label);
        return new ThrowFlag($this->currentError);
    }

    public function recoverFromError(): self
    {
        $this->currentError = null;
        return $this;
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
            case '**':
                return pow($a, $b);
            case '..':
                return $a . $b;
            case '==':
                if (($a instanceof Label) && ($b instanceof Label)) {
                    return $a->getName() == $b->getName();
                }
                return $a == $b;
            case '!=':
                return $a != $b;
            case '<=':
                return $a <= $b;
            case '>=':
                return $a >= $b;
            case '>':
                return $a > $b;
            case '<':
                return $a < $b;
            default:
                throw new InvalidASTException('Unknown operator');
        }
    }
}
