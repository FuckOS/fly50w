<?php

namespace Fly50w\VM\Internal;

use Fly50w\VM\Types\Label;

class ThrowFlag
{
    public function __construct(
        public Label $error
    ) {
    }
}
