<?php

namespace Fly50w\Parser\AST;

class FunctionNode extends ExpressionNode
{
    /**
     * Params
     *
     * @var string[]
     */
    public array $params = [];
    public bool $_in_param = false;

    public function __construct()
    {
    }

    /**
     * Get params
     *
     * @return string[]
     */
    public function getParams(): array
    {
        return $this->params;
    }

    public function addParam(string $name): self
    {
        $this->params[] = $name;
        return $this;
    }
}
