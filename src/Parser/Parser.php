<?php

namespace Fly50w\Parser;

use Exception;
use Fly50w\Lexer\Token;
use Fly50w\Lexer\Scalar;
use Fly50w\Parser\AST\Node;
use Fly50w\Parser\AST\Scope;
use Fly50w\Parser\AST\RootNode;
use Fly50w\Parser\AST\BraceExpressionNode;
use Fly50w\Parser\AST\LiteralNode;
use Fly50w\Parser\AST\FunctionCallNode;
use Fly50w\Parser\AST\ForNode;
use Fly50w\Parser\AST\ArgumentNode;
use Fly50w\Parser\AST\AssignNode;
use Fly50w\Parser\AST\BreakNode;
use Fly50w\Parser\AST\FunctionNode;
use Fly50w\Parser\AST\OperatorNode;
use Fly50w\Parser\AST\ReturnNode;
use Fly50w\Parser\AST\StatementNode;
use Fly50w\Parser\AST\VariableNode;
use Fly50w\Parser\AST\Internal\LetFlagNode;
use Fly50w\Exceptions\SyntaxErrorException;
use Fly50w\Exceptions\UnmatchedBracketsException;
use Fly50w\Parser\AST\ArrayAccessNode;
use Fly50w\Parser\AST\ExceptNode;
use Fly50w\Parser\AST\LabelNode;
use Fly50w\Parser\AST\ThrowNode;
use Fly50w\Parser\AST\TryNode;
use Symfony\Component\VarDumper\VarDumper;

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
            // if ($curr->isFull()) {
            //     $curr = $curr->getParent();
            // }
            switch ($token->type) {
                case Token::T_SCALAR:
                    $value = $token->scalar->value;
                    switch ($token->scalar->type) {
                        case Scalar::T_NUMERIC:
                            $value = $value + 0;
                            break;
                        case Scalar::T_STR:
                            $value = "$value";
                            break;
                        case Scalar::T_NULL:
                            $value = null;
                            break;
                        case Scalar::T_BOOL:
                            if (is_string($value)) {
                                $value = "true" === strtolower($value);
                            }
                            break;
                    }
                    $curr->addChild(new LiteralNode(
                        value: $value,
                        type: $token->scalar->type
                    ));
                    break;
                case Token::T_IDENTIFY:
                    $curr->addChild(new VariableNode($token->value));
                    break;
                case Token::T_ANNOTATION:
                    $curr->addChild(new LabelNode($token->value));
                    break;
                case Token::T_KEYWORD:
                    switch ($token->value) {
                        case 'fn':
                            $fn = new FunctionNode();
                            $curr->addChild($fn);
                            $curr = $fn;
                            $curr->_in_param = true;
                            break;
                        case 'except':
                            $en = new ExceptNode();
                            $curr->addChild($en);
                            $curr = $en;
                            $curr->_in_param = true;
                            break;
                        case 'for':
                            $fn = new ForNode();
                            $curr->addChild($fn);
                            $curr = $fn;
                            break;
                        case 'try':
                            $tn = new TryNode();
                            $curr->addChild($tn);
                            $curr = $tn;
                            break;
                        case 'throw':
                            if (!$curr instanceof StatementNode || !$curr->isEmpty()) {
                                throw new SyntaxErrorException('Return should be an independent expression');
                            }
                            $rn = new ThrowNode();
                            $curr->addChild($rn);
                            $curr = $rn;
                            break;
                        case 'return':
                            if (!$curr instanceof StatementNode || !$curr->isEmpty()) {
                                throw new SyntaxErrorException('Return should be an independent expression');
                            }
                            $rn = new ReturnNode();
                            $curr->addChild($rn);
                            $curr = $rn;
                            break;
                        case 'break':
                            if (!$curr instanceof StatementNode || !$curr->isEmpty()) {
                                throw new SyntaxErrorException('Break should be an independent expression');
                            }
                            $bn = new BreakNode();
                            $curr->addChild($bn);
                            $curr = $bn;
                            break;
                        case 'let':
                            $curr->addChild(new LetFlagNode());
                            break;
                    }
                    break;
                case Token::T_SYMBOL:
                    switch ($token->value) {
                        case '=':
                            $prev = $curr->popChild();
                            $let_mark = false;
                            if (!$curr->isEmpty()) {
                                $child = $curr->getTopChild();
                                if ($child instanceof LetFlagNode) {
                                    $curr->popChild();
                                    $let_mark = true;
                                }
                            }
                            $an = new AssignNode($let_mark);
                            $curr->addChild($an);
                            $curr = $an;
                            $curr->addChild($prev);
                            break;
                        case '+':
                        case '-':
                        case '*':
                        case '/':
                        case '%':
                        case '..':
                        case '**':
                        case '==':
                        case '!=':
                        case '<=':
                        case '>=':
                        case '<':
                        case '>':
                        case '.':
                        case '=>':
                            $curr = (new OperatorNode($token->value))->addChild($curr->popChild())->setParent($curr);
                            $curr->getParent()->addChild($curr);
                            break;
                        case ';':
                            while (!($curr instanceof StatementNode)) {
                                $curr = $curr->getParent();
                            }
                            $curr = $curr->getParent();
                            $sn = new StatementNode();
                            $curr->addChild($sn);
                            $curr = $sn;
                            break;
                        case '(':
                            $brackets->push('(');
                            if (($curr instanceof FunctionNode ||
                                    $curr instanceof ExceptNode) &&
                                $curr->_in_param
                            ) {
                                break;
                            }
                            $topChild = $curr->getTopChild();
                            if (
                                ($topChild instanceof VariableNode) &&
                                (!(($curr instanceof AssignNode ||
                                    $curr instanceof OperatorNode) &&
                                    (!$curr->isFull())))
                            ) {
                                $fcn = new FunctionCallNode($topChild->getName());
                                $curr->popChild();
                                unset($topChild);
                                $curr->addChild($fcn);
                                $curr = $fcn;
                                break;
                            }
                            $en = new BraceExpressionNode();
                            $curr->addChild($en);
                            $curr = $en;
                            break;
                        case ')':
                            $last = $brackets->pop();
                            if ($last != '(') {
                                throw new UnmatchedBracketsException();
                            }
                            while ($curr->isFull()) {
                                $curr = $curr->getParent();
                            }
                            if (($curr instanceof FunctionNode ||
                                    $curr instanceof ExceptNode) &&
                                $curr->_in_param
                            ) {
                                // $curr->_in_param = false;
                                break;
                            }
                            if ($curr instanceof FunctionCallNode) {
                                $curr = (new ArgumentNode())->addChildren($curr->purgeChildren())->setParent($curr);
                                $curr->getParent()->addChild($curr);
                            }
                            if (
                                $curr instanceof BraceExpressionNode ||
                                $curr instanceof FunctionCallNode
                            ) {
                                $curr = $curr->getParent();
                            }
                            if ($curr instanceof ArgumentNode) {
                                $curr = $curr->getParent()->getParent();
                            }
                            // TODO: finish this
                            break;
                        case '{':
                            $brackets->push('{');
                            if ($curr instanceof FunctionNode) {
                                if ($curr->_in_param) {
                                    $curr->_in_param = false;
                                    goto END_PARAM;
                                }
                                BRACKET_RECALL:
                                $sn = new StatementNode();
                                $curr->addChild($sn);
                                $curr = $sn;
                                break;
                            }
                            if ($curr instanceof ExceptNode) {
                                if ($curr->_in_param) {
                                    $curr->_in_param = false;
                                    goto END_LABEL;
                                }
                                BRACKET_RECALL_EXCEPT:
                                $sn = new StatementNode();
                                $curr->addChild($sn);
                                $curr = $sn;
                                break;
                            }
                            if (
                                $curr instanceof ForNode ||
                                $curr instanceof TryNode
                            ) {
                                $sn = new StatementNode();
                                $curr->addChild($sn);
                                $curr = $sn;
                            }
                            // TODO: finish this
                            break;
                        case '}':
                            $last = $brackets->pop();
                            if ($last != '{') {
                                throw new UnmatchedBracketsException();
                            }
                            if ($curr instanceof StatementNode) {
                                $curr = $curr->getParent();
                                if ($curr->getTopChild()->isEmpty()) {
                                    $curr->popChild();
                                }
                            }
                            if (
                                $curr instanceof FunctionNode ||
                                $curr instanceof ExceptNode
                            ) {
                                $curr = $curr->getParent();
                            }
                            if (
                                $curr instanceof ForNode ||
                                $curr instanceof TryNode
                            ) {
                                $curr = $curr->getParent();
                            }
                            break;
                        case ',':
                            if ($curr->isFull()) {
                                $curr = $curr->getParent();
                            }
                            if ($curr instanceof FunctionCallNode) {
                                $curr = (new ArgumentNode())->addChildren($curr->purgeChildren())->setParent($curr);
                                $curr->getParent()->addChild($curr);
                                break;
                            }
                            if ($curr instanceof FunctionNode) {
                                if ($curr->_in_param) {
                                    END_PARAM:
                                    $children = $curr->purgeChildren();
                                    if (count($children) > 0) {
                                        if (count($children) != 1 || !($children[0] instanceof VariableNode)) {
                                            throw new SyntaxErrorException("Expected ',' received T_EXPR");
                                        }
                                        $curr->addParam($children[0]->getName());
                                    }
                                    if (!$curr->_in_param) {
                                        goto BRACKET_RECALL;
                                    }
                                    break;
                                }
                            }
                            if ($curr instanceof ExceptNode) {
                                if ($curr->_in_param) {
                                    END_LABEL:
                                    $children = $curr->purgeChildren();
                                    if (count($children) > 0) {
                                        if (count($children) != 1 || !($children[0] instanceof LabelNode)) {
                                            throw new SyntaxErrorException("Expected ',' received T_EXPR");
                                        }
                                        $curr->addLabel($children[0]->getName());
                                    }
                                    if (!$curr->_in_param) {
                                        goto BRACKET_RECALL_EXCEPT;
                                    }
                                    break;
                                }
                            }
                            break;
                        case '[':
                            $brackets->push('[');
                            $aan = new ArrayAccessNode();
                            $aan->addChild($curr->popChild());
                            $curr->addChild($aan);
                            $curr = $aan;
                            break;
                        case ']':
                            $last = $brackets->pop();
                            if ($last != '[') {
                                throw new UnmatchedBracketsException();
                            }
                            while (!($curr instanceof ArrayAccessNode)) {
                                $curr = $curr->getParent();
                            }
                            $curr = $curr->getParent();
                            break;
                    }
                    break;
            }
        }
        return $root;
    }

    public function assignScope(Node $node, ?int $childId = null): Node
    {
        if ($node instanceof RootNode) {
            $node->setScope(new Scope(''));
        } else {
            // if (
            //     $childId !== null &&
            //     ($node->getParent() instanceof FunctionNode)
            // ) {
            //     // TODO: add try catch for
            //     $node->setScope(
            //         $node->getParent()->getScope()->subScope('0')
            //     );
            // } else {
            //     $node->setScope($node->getParent()->getScope());
            // }
            // TODO: add try catch for
            if (
                $node instanceof FunctionNode ||
                $node instanceof ForNode ||
                $node instanceof TryNode ||
                $node instanceof ExceptNode
            ) {
                $node->setScope($node->getParent()->getScope()->subScope($childId));
            } else {
                $node->setScope($node->getParent()->getScope());
            }
        }
        foreach ($node->getChildren() as $id => $child) {
            $this->assignScope($child, $id);
        }
        return $node;
    }
}
