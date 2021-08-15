<?php

namespace Fly50w\CLI;

use Fly50w\Lexer\Lexer;
use Fly50w\Parser\Parser;
use Fly50w\Version;
use League\CLImate\CLImate;

class Application
{

    protected ?CLImate $cli;
    protected string $head = '';

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
                'description' => 'File to compile'
            ],
            'run' => [
                'prefix' => 'r',
                'longPrefix' => 'run',
                'description' => 'File to run'
            ],
            'interactive' => [
                'prefix' => 'i',
                'longPrefix' => 'interactive',
                'description' => 'Use interactive mode',
                'noValue' => true
            ],
            'force' => [
                'prefix' => 'f',
                'longPrefix' => 'force',
                'description' => 'Force overwrite output',
                'noValue' => true
            ],
            'output' => [
                'prefix' => 'o',
                'longPrefix' => 'output',
                'description' => 'Output file name'
            ]
        ]);
        $this->head = '<bold><green>' .
            Version::getArt() .
            '</green></bold>' .
            '<underline><yellow> Version ' .
            Version::getVersion() .
            "</yellow></underline>\r\n";
        $this->cli->description(
            $this->head .
                '<bold><cyan>DESCRIPTION</cyan></bold> Fly50w helps ' .
                'you create programs with more than <bold>500k</bold> ' .
                'lines of code easily.'
        );
    }

    public function run(array $argv)
    {
        $this->cli->arguments->parse($argv);
        if ($this->cli->arguments->defined('help')) {
            $this->cli->usage($argv);
            return;
        }
        $flag = false;
        if ($this->cli->arguments->defined('compile')) {
            $this->cli->out($this->head);
            $input = $this->cli->arguments->get('compile');
            $output = $input . '.f5wc';
            if ($this->cli->arguments->defined('output')) {
                $output = $this->cli->arguments->get('output');
            }
            $this->doCompile($input, $output);
            $flag = true;
        }
        if ($this->cli->arguments->defined('run')) {
            $flag = true;
        }
        if (!$flag) {
            $this->cli->usage($argv);
        }
    }

    function doCompile(string $input, string $output)
    {
        if (!file_exists($input)) {
            $this->cli->bold()->red()->backgroundYellow("No such file: $input");
            exit(1);
        }
        if (file_exists($output) && (!$this->cli->arguments->defined('force'))) {
            $prompt = $this->cli->radio(
                prompt: "<bold><red>[ERROR]</red></bold> File <bold><cyan>$output</cyan></bold>" .
                    " already exists.\r\n<bold>=> <magenta>Overwrite?</magenta></bold>",
                options: [
                    'Yes, overwrite please',
                    'No, don\'t do anything'
                ]
            );
            if ($prompt->prompt() != 'Yes, overwrite please') {
                $this->cli->info('Aborting.');
                return;
            }
            $this->cli->br();
        }

        $this->cli->info("Initializing compiler... ");

        $lexer = new Lexer();
        $parser = new Parser();

        $this->cli->info("Merging code files... ")->br();

        try {
            $code = Merger::mergeFile($input, $this->cli);
        } catch (\Exception $e) {
            $this->cli->out($e->getMessage());
            exit(1);
        }

        $l_number = Merger::getLineNumber($code);

        $this->cli->out("<green><bold>Line number: </bold></green><underline>$l_number</underline>");
        $this->cli->out("<green><bold>Output file: </bold></green><underline>$output</underline>");
        $this->cli->br();

        if ($l_number < 500000) {
            $this->cli
                ->red("<bold>WARNING: you'd better give me more than 500k lines of code.</bold>")
                ->br();
        }

        $this->cli->info("Lexing code... ");

        $bare_tokens = $lexer->tokenize($code);
        $tokens = $lexer->standardize($bare_tokens);

        $this->cli->info("Parsing code... ");

        $ast = $parser->parse($tokens, $input);

        $this->cli->info("Generating fly50vm recognizable instructions...");
        // sleep(1);
        file_put_contents($output, serialize($ast));
        $this->cli->br();

        $this->cli->out(
            '<bold><green>Compile done.</green></bold>' .
                "\r\n" .
                "Run your program with <bold><cyan>{$GLOBALS['argv'][0]} -r $output</cyan></bold> and enjoy!"
        );
    }
}
