<?php

namespace Fly50w\Parser\AST;

class FunctionCallNode extends ExpressionNode
{
    public function __construct(
        public string $function = ''
    ) {
    }

    public function getFunction(): string
    {
        return $this->function;
    }

    public function setFunction(string $function): self
    {
        $this->function = $function;
        return $this;
    }
}
