<?php

namespace Fly50w\Parser\AST;

class RootNode extends Node
{
    public function __construct(
        public string $filename = 'IN_MEMORY_CODE'
    ) {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }
}
