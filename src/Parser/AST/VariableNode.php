<?php

namespace Fly50w\Parser\AST;

class VariableNode extends ExpressionNode
{
    protected int $maxNodes = 0;

    public function __construct(
        public string $name
    ) {
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
