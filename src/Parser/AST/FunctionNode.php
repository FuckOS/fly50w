<?php

namespace Fly50w\Parser\AST;

class FunctionNode extends ExpressionNode
{
    public array $params = [];
    public bool $_in_param = false;

    public function __construct()
    {
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function addParam($name): self
    {
        $this->params[] = $name;
        return $this;
    }
}
