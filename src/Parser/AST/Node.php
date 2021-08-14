<?php

namespace Fly50w\Parser\AST;

class Node
{
    protected ?Node $parent = null;
    /**
     * @var Node[]
     */
    public array $children = [];

    public function getParent(): ?Node
    {
        return $this->parent;
    }

    public function setParent(Node $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function addChild(Node $node): self
    {
        $this->children[] = $node;
        $node->setParent($this);
        return $this;
    }

    public function getChildren(): array
    {
        return $this->children;
    }
}
