<?php

namespace Fly50w;

use Fly50w\CLI\Merger;
use Fly50w\Lexer\Lexer;
use Fly50w\Parser\AST\RootNode;
use Fly50w\Parser\Parser;
use Fly50w\VM\VM;

class Facade
{
    public static function create(): self
    {
        return new Facade();
    }

    protected Lexer $lexer;
    protected VM $vm;
    protected Parser $parser;

    public function __construct()
    {
        $this->lexer = new Lexer;
        $this->vm = new VM;
        $this->parser = new Parser;
    }

    public function getVM(): VM
    {
        return $this->vm;
    }

    public function runFile(string $filename): mixed
    {
        $code = Merger::mergeFile($filename);
        $ast = $this->parseCode($code, $filename);

        return $this->vm->execute($ast);
    }

    public function run(
        string $code,
        string $filename = 'EMBEDDED_CODE'
    ): mixed {
        return $this->vm->execute(
            $this->parseCode(
                Merger::mergeFile('**$') . $code,
                $filename
            )
        );
    }

    public function eval(string $code): mixed
    {
        return $this->vm->execute($this->parseCode($code));
    }

    public function parseCode(
        string $code,
        string $filename = 'INTERNAL_CODE'
    ): RootNode {
        $tokens = $this->lexer->tokenize($code);
        $tokens = $this->lexer->standardize($tokens);
        $ast = $this->parser->parse($tokens, $filename);
        $ast = $this->parser->assignScope($ast);
        return $ast;
    }
}
