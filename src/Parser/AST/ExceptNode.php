<?php

namespace Fly50w\Parser\AST;

class ExceptNode extends ExpressionNode
{
    /**
     * Labels
     *
     * @var string[]
     */
    public array $labels;

    public function __construct()
    {
    }

    public function getLabels(): array
    {
        return $this->labels;
    }

    public function setLabels(array $labels): self
    {
        $this->labels = $labels;
        return $this;
    }

    public function addLabel(string $label): self
    {
        $this->labels[] = $label;
        return $this;
    }
}
