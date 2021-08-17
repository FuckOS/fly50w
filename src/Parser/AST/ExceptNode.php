<?php

namespace Fly50w\Parser\AST;

class ExceptNode extends ExpressionNode
{
    public string $label;

    public function __construct()
    {
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLable(string $label): self
    {
        $this->label = $label;
        return $this;
    }
}
