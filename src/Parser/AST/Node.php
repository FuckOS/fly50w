<?php

namespace Fly50w\Parser\AST;

class Node
{
    protected ?Node $parent = null;
    /**
     * @var Node[]
     */
    public array $children = [];

    protected int $maxNodes = -1;

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
        if ($this->isFull()) {
            throw new \Exception("Node is full");
        }
        $this->children[count($this->children)] = $node;
        $node->setParent($this);
        return $this;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function popChild(): ?Node
    {
        if (count($this->children) > 0) {
            $top = count($this->children) - 1;
            $child = $this->children[$top];
            unset($this->children[$top]);
            return $child;
        } else {
            return null;
        }
    }

    public function isFull(): bool
    {
        return ($this->maxNodes === -1) ?
            false : (count($this->children) >= $this->maxNodes);
    }
}
