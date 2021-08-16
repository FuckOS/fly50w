<?php

namespace Fly50w\CLI;

use Fly50w\Facade;
use League\CLImate\CLImate;
use Symfony\Component\VarDumper\VarDumper;

class REPL
{
    protected Facade $facade;

    public function __construct()
    {
        $this->facade = new Facade;
    }

    public function loop(): void
    {
        while ($this->run()) {
            // do nothing...
        }
    }

    public function run(): bool
    {
        $input = trim($this->input());
        if ($input === 'exit' || $input === 'quit') {
            return false;
        }
        try {
            if (substr($input, 0, 8) === '!import ') {
                $this->facade->runFile(substr($input, 8));
            } else {
                $rslt = $this->facade->run($input, 'REPL_CODE');
                $this->output($rslt);
            }
        } catch (\Exception $e) {
            (new CLImate)->bold()->backgroundBlue()->error($e->getMessage());
        } catch (\Error $e) {
            (new CLImate)->bold()->backgroundBlue()->error($e->getMessage());
        }
        return true;
    }

    public function input(): string
    {
        $line = readline('> ');
        readline_add_history($line);
        if ($line === false) return 'exit';
        return $line;
    }

    public function output(mixed $data): void
    {
        echo "==> ";
        VarDumper::dump($data);
    }
}
