<?php

namespace Fly50w\Parser\AST;

class OperatorNode extends ExpressionNode
{
    protected int $maxNodes = 2;

    public function __construct(
        public string $operator = '+'
    ) {
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function setOperator(string $operator): self
    {
        $this->operator = $operator;
        return $this;
    }
}
