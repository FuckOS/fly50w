<?php

namespace Fly50w\Parser\AST;

class Scope
{
    public string $scope = '';

    public function __construct(
        Scope|string|null $scope = null
    ) {
        if ($scope !== null) {
            $this->setScope($scope);
        }
    }

    public function __toString(): string
    {
        return $this->scope;
    }

    public function setScope(Scope|string $scope): self
    {
        if ($scope instanceof Scope) {
            $this->scope = $scope->__toString();
        } else {
            $this->scope = $scope;
        }
        return $this;
    }

    public function isRoot(): bool
    {
        return $this->scope === '';
    }

    public function upperScope(): Scope
    {
        $pos = strripos(
            needle: ':',
            haystack: $this->scope
        );
        return new Scope(substr($this->scope, 0, $pos));
    }

    public function subScope(string $scopeName): Scope
    {
        return new Scope($this->scope . ':' . $scopeName);
    }
}
