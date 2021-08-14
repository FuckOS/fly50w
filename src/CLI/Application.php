<?php

namespace Fly50w\CLI;

use Fly50w\Version;
use League\CLImate\CLImate;

class Application
{

    protected $cli;

    public function __construct()
    {
        $this->cli = new CLImate();
        $this->cli->arguments->add([
            'help' => [
                'prefix' => 'h',
                'longPrefix' => 'help',
                'description' => 'Show help text',
                'noValue' => true
            ],
            'compile' => [
                'prefix' => 'c',
                'longPrefix' => 'compile',
                'description' => 'File to compile',
            ],
            'interactive' => [
                'prefix' => 'i',
                'longPrefix' => 'interactive',
                'description' => 'Use interactive mode',
                'noValue' => true
            ],
            'output' => [
                'prefix' => 'o',
                'longPrefix' => 'output',
                'description' => 'Output file name'
            ]
        ]);
        $this->cli->description(
            '<bold><green>' .
                Version::getArt() .
                '</green></bold>' .
                '<underline><yellow> Version ' .
                Version::getVersion() .
                "</yellow></underline>\r\n" .
                '<bold><red>DESCRIPTION</red></bold> Fly50w helps ' .
                'you create programs with more than <brown>500k</brown> ' .
                'lines of code easily.'
        );
    }

    public function run(array $argv)
    {
        $this->cli->arguments->parse($argv);
        if ($this->cli->arguments->defined('help')) {
            $this->cli->usage($argv);
        }
    }
}
