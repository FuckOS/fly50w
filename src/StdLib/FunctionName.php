<?php

namespace Fly50w\StdLib;

use Attribute;

#[Attribute]
class FunctionName
{
    public function __construct(
        public string $name
    ) {
    }
}
