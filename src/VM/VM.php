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
use Fly50w\Exceptions\SyntaxErrorException;
use Fly50w\Parser\AST\ArrayAccessNode;
use Fly50w\Parser\AST\ExceptNode;
use Fly50w\Parser\AST\LabelNode;
use Fly50w\Parser\AST\ThrowNode;
use Fly50w\StdLib\Internal;
use Fly50w\StdLib\WebServer;
use Fly50w\VM\Internal\PurgeResultFlag;
use Fly50w\VM\Internal\ThrowFlag;

class VM
{
    public array $states = [
        'INF' => INF
    ];

    public ?Label $currentError = null;
    public ?string $lastErrorMessage = null;

    public function __construct()
    {
        new Internal($this);
        new WebServer($this);
    }

    public function execute(RootNode $ast): mixed
    {
        $last_rslt = null;
        foreach ($ast->getChildren() as $child) {
            $rslt = $this->runNode($child);
            if ($rslt instanceof ReturnFlag) {
                return $rslt->data;
            }
            if ($this->currentError !== null) {
                throw new RuntimeException("Uncaught error: " . $this->currentError->getName());
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
            if ($node->countChildren() != 2) {
                throw new InvalidASTException('Excepted VariableNode child for AssignNode');
            }
            $name = '';
            $offset = [];
            $bottom_child = $node->getBottomChild();
            while ($bottom_child instanceof ArrayAccessNode) {
                $offset[] = $bottom_child->countChildren() == 2 ?
                    $this->runNode($bottom_child->getChildren()[1]) : INF;
                $bottom_child = $bottom_child->getBottomChild();
            }
            if ($bottom_child instanceof VariableNode) {
                $name = $bottom_child->getName();
            } else {
                throw new InvalidASTException('Excepted VariableNode child for AssignNode');
            }
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
            $curr = &$this->states[$name];
            $offset = array_reverse($offset);
            foreach ($offset as $k) {
                if (!is_array($curr)) {
                    return $this->throwError('notArrayAccessibleError');
                }
                if ($k === INF) {
                    $curr = &$curr[count($curr) - 1];
                } else {
                    $curr = &$curr[$k];
                }
            }
            $val = $this->runNode($node->getChildren()[1]);
            $curr = $val;
            return $val;
        }
        if ($node instanceof FunctionNode) {
            return function (array $args, VM $vm) use ($node) {
                $params = $node->getParams();
                if (count($args) != count($params)) {
                    return $this->throwError(
                        'wrongArgumentNumberError',
                        'Wrong argument number calling, ' .
                            count($params) .
                            ' expected'
                    );
                }
                $params = array_map(fn ($v) => $node->getScope() . $v, $params);
                $backup = [];
                foreach ($params as $k => $v) {
                    if (isset($vm->states[$v])) $backup[$v] = $vm->states[$v];
                    $vm->states[$v] = $args[$k];
                }
                $last_rslt = null;
                foreach ($node->getChildren() as $child) {
                    $rslt = $vm->runNode($child);
                    if ($rslt instanceof ReturnFlag) {
                        $last_rslt = $rslt->data;
                        goto RESTORE_STATE;
                    }
                    if ($rslt instanceof ThrowFlag) {
                        $last_rslt = $rslt;
                        goto RESTORE_STATE;
                    }
                    $last_rslt = $rslt ?? $last_rslt;
                }
                RESTORE_STATE:
                foreach ($params as $k => $v) {
                    unset($vm->states[$v]);
                }
                foreach ($backup as $k => $v) {
                    $vm->states[$k] = $v;
                }
                return $last_rslt;
            };
        }
        if ($node instanceof FunctionCallNode) {
            $func = $this->getVariable($node->getFunction(), $node->getScope());
            if ($func instanceof ThrowFlag) {
                return $func;
            }
            if (!is_callable($func)) {
                $this->throwError(
                    'notCallableError',
                    'Variable ' . $node->getFunction() . ' is not callable'
                );
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
        if ($node instanceof ArrayAccessNode) {
            $childs_count = $node->countChildren();
            if ($childs_count != 2) {
                throw new InvalidASTException('Should pass 2 children for ArrayAccessNode');
            }
            $arr = $this->runNode($node->getChildren()[0]);
            $offset = $this->runNode($node->getChildren()[1]);
            if (!is_array($arr)) {
                return $this->throwError('notArrayAccessibleError');
            }
            if (!isset($arr[$offset])) {
                return $this->throwError('undefinedKeyError');
            }
            return $arr[$offset];
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
                return $this->throwError('undefinedVariableError', 'Undefined variable: ' . $name);
            }
            $scope = $scope->upperScope();
        }
        return $this->states[$scope . $name];
    }

    public function assignVariable(string $name, mixed $value, ?Scope $nodeScope = null): self
    {
        if ($nodeScope === null) {
            $this->states[$name] = $value;
        } else {
            $this->states[$nodeScope->scope . $name] = $value;
        }
        return $this;
    }

    public function assignNativeFunction(string $name, callable $func): self
    {
        $this->assignVariable($name, function (array $args, VM $vm) use ($func) {
            return call_user_func_array($func, $args);
        });
        return $this;
    }

    public function throwError(string $label, ?string $msg = null): ThrowFlag
    {
        $this->currentError = new Label($label);
        $this->lastErrorMessage = $msg ?? $this->lastErrorMessage;
        return new ThrowFlag($this->currentError);
    }

    public function getLastErrorMessage(): ?string
    {
        return $this->lastErrorMessage;
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
        if ($node->getOperator() === '.') {
            $c1 = $node->getChildren()[1];
            if (!($c1 instanceof VariableNode)) {
                throw new InvalidASTException('Should be VariableNode using `.` operator');
            }
            $b = $c1->getName();
            if (!isset($a[$b])) {
                return $this->throwError('undefinedKeyError');
            }
            return $a[$b];
        }
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
            case '=>':
                return [$a, $b];
            default:
                throw new InvalidASTException('Unknown operator');
        }
    }
}
