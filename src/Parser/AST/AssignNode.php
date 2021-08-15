<?php

namespace Fly50w\Parser\AST;

class AssignNode extends ExpressionNode
{
    protected int $maxNodes = 2;

    public function __construct(
        public bool $let = false
    ) {
    }

    public function isLet(): bool
    {
        return $this->let;
    }

    public function setLet(bool $let): self
    {
        $this->let = $let;
        return $this;
    }
}
