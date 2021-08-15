<?php

namespace Fly50w\Parser\AST;

class ReturnNode extends ExpressionNode
{
    protected int $maxNodes = 1;

    public function __construct()
    {
    }
}
