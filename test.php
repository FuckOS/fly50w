<?php

use Fly50w\Lexer\Lexer;
use Symfony\Component\VarDumper\VarDumper;

require_once "vendor/autoload.php";

$lexer = new Lexer;

$tokens = $lexer->tokenize($argv[1]);

VarDumper::dump($tokens);
