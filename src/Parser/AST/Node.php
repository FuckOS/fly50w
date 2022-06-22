<?php

namespace Fly50w\Parser\AST;

class Node
{
    protected ?Node $parent = null;
    protected int $maxNodes = -1;
    protected ?Scope $scope = null;
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

    public function getScope(): ?Scope
    {
        return $this->scope;
    }

    public function setScope(Scope $scope): self
    {
        $this->scope = $scope;
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

    /**
     * Add children
     *
     * @param Node[] $nodes
     * @return self
     */
    public function addChildren(array $nodes): self
    {
        $this->children = array_merge($this->children, $nodes);
        return $this;
    }

    public function purgeChildren(string $type = null): array
    {
        $i = 0;
        if ($type === null) {
            $i = 0;
        } else foreach ($this->children as $k => $v) {
            if (!($v instanceof $type)) {
                $i = $k;
                break;
            }
        }
        $children = array_slice($this->children, $i);
        $this->children = array_slice($this->children, 0, $i);
        return $children;
    }

    public function isEmpty(): bool
    {
        return count($this->children) == 0;
    }

    /**
     * @return Node[]
     */
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

    public function getTopChild(): ?Node
    {
        if (count($this->children) > 0) {
            $child = $this->children[count($this->children) - 1];
            return $child;
        } else {
            return null;
        }
    }

    public function getBottomChild(): ?Node
    {
        if (count($this->children) > 0) {
            return $this->children[0];
        } else {
            return null;
        }
    }

    public function countChildren(): int
    {
        return count($this->children);
    }

    public function isFull(): bool
    {
        return ($this->maxNodes === -1) ?
            false : (count($this->children) >= $this->maxNodes);
    }
}
