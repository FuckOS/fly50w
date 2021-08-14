<?php

namespace Fly50w\Lexer;

class Token
{
    const T_KEYWORD = 'T_KEYWORD';
    const T_IDENTIFY = 'T_IDENTIFY';
    const T_SCALAR = 'T_SCALAR';
    const T_NOOP = 'T_NOOP';
    const T_ANNOTATION = 'T_ANNOTATION';
    const T_SYMBOL = 'T_SYMBOL';
    public function __construct(
        public string $type = self::T_NOOP,
        public string $value = '',
        public ?Scalar $scalar = null
    ) {
    }
}
