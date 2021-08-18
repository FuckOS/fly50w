<?php

namespace Fly50w\Parser\AST;

class ThrowNode extends ExpressionNode
{
    protected int $maxNodes = 1;

    public function __construct()
    {
    }
}
