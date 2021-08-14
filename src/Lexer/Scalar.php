<?php

namespace Fly50w\Lexer;

class Scalar
{
    const T_NUMERIC = 'numeric';
    const T_STR = 'string';
    const T_BOOL = 'bool';
    const T_NULL = 'null';
    public function __construct(
        public string $type = '',
        public mixed $value = null
    ) {
    }
}
