<?php

namespace Fly50w\Parser\AST;

class LiteralNode extends Node
{
    public function __construct(
        public mixed $value,
        public string $type = ''
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setValue(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function addChildren(Node $node): Node
    {
        throw new \Exception("Cannot add children to LiteralNode");
        return $this;
    }
}
