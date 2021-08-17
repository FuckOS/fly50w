<?php

namespace Fly50w\CLI;

use Fly50w\Lexer\Lexer;
use Fly50w\Parser\Parser;
use Fly50w\Version;
use Fly50w\VM\VM;
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
        if ($this->cli->arguments->defined('interactive')) {
            $this->cli->out($this->head);
            $this->cli->backgroundBlue()
                ->white()->out('                                       ');
            $this->cli->backgroundBlue()
                ->white()->out('           Welcome to Fly50w           ');
            $this->cli->backgroundBlue()
                ->white()->out('                                       ');
            $this->cli->br();
            $input = 'main.f5w';
            $output = 'main.f5w.f5wc';
            while (true) {
                $prompt = $this->cli->checkboxes(
                    prompt: "<bold>=> <magenta>What would you like to do?</magenta></bold>",
                    options: [
                        'Compile a program',
                        'Run a program',
                        'Run REPL',
                        'Show help text',
                        'Exit'
                    ]
                );
                $in = $prompt->prompt();
                if (in_array('Exit', $in)) {
                    $this->cli->info('Bye.');
                    return;
                }
                if (in_array('Show help text', $in)) {
                    $this->cli->usage($argv);
                }
                if (in_array('Compile a program', $in)) {
                    $this->cli->br()->out('<bold>== This is Fly50w compiler ==</bold>');
                    $input = $this->cli
                        ->input('<bold>=> <magenta>Input filename</magenta></bold> [' . $input . ']:')
                        ->defaultTo($input)
                        ->prompt();
                    $output = $input . '.f5wc';
                    $output = $this->cli
                        ->input('<bold>=> <magenta>Output filename</magenta></bold> [' . $output . ']:')
                        ->defaultTo($output)
                        ->prompt();
                    $this->cli->br()->info('Please wait... ');
                    $this->doCompile($input, $output);
                }
                if (in_array('Run a program', $in)) {
                    $this->cli->br()->out('<bold>== This is Fly50w VM ==</bold>');
                    $output = $this->cli
                        ->input('<bold>=> <magenta>Filename</magenta></bold> [' . $output . ']:')
                        ->defaultTo($output)
                        ->prompt();
                    $this->doRun($output);
                }
                if (in_array('Run REPL', $in)) {
                    $this->cli->br()->out('Fly50w REPL');
                    (new REPL)->loop();
                }
                $this->cli->br();
            }
            return;
        }
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
            $output = $this->cli->arguments->get('run');
            $this->doRun($output);
            $flag = true;
        }
        if (!$flag) {
            $this->cli->usage($argv);
        }
    }

    protected function doRun(string $output)
    {
        $vm = new VM();
        if (!file_exists($output)) {
            $this->cli->bold()->red()->backgroundYellow("No such file: $output");
            if (!$this->cli->arguments->defined('interactive')) {
                exit(1);
            }
            return;
        }
        $out = file_get_contents($output);
        $ast = unserialize(gzdecode($out));
        // $ast = unserialize($out);
        if (!$ast) {
            $this->cli->bold()->red()->backgroundYellow("File format error: $output");
            if (!$this->cli->arguments->defined('interactive')) {
                exit(1);
            }
            return;
        }
        $vm->execute($ast);
    }

    protected function doCompile(string $input, string $output)
    {
        if (!file_exists($input)) {
            $this->cli->bold()->red()->backgroundYellow("No such file: $input");
            if (!$this->cli->arguments->defined('interactive')) {
                exit(1);
            }
            return;
        }
        if (file_exists($output) && (!$this->cli->arguments->defined('force'))) {
            $prompt = $this->cli->radio(
                prompt: "<bold><yellow>[WARNING]</yellow></bold> File <bold><cyan>$output</cyan></bold>" .
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
            if (!$this->cli->arguments->defined('interactive')) {
                exit(1);
            }
            return;
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

        $this->cli->info("Analyzing AST scope relationship... ");
        $ast = $parser->assignScope($ast);

        $this->cli->info("Generating fly50vm recognizable instructions...");
        // file_put_contents($output, gzencode(serialize($ast), 9));
        file_put_contents($output, @var_export($ast, true));
        $this->cli->br();

        $this->cli->out(
            '<bold><green>Compile done.</green></bold>' .
                "\r\n" .
                "Run your program with <bold><cyan>{$GLOBALS['argv'][0]} -r $output</cyan></bold> and enjoy!"
        );
    }
}
