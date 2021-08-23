<?php

namespace Fly50w\CLI;

use League\CLImate\CLImate;

class Merger
{
    public function __construct(
        protected array $importDirectories = []
    ) {
        $this->addImportDirectory('.');
        if (is_dir(dirname(dirname(__DIR__)) . '/std')) {
            $this->addImportDirectory(dirname(dirname(__DIR__)) . '/std');
        }
    }

    public function addImportDirectory(array|string $dir): self
    {
        if (is_array($dir)) {
            $this->importDirectories = array_merge($this->importDirectories, $dir);
        } else {
            $this->importDirectories[] = $dir;
        }
        return $this;
    }

    public function mergeFile(string $filename, ?CLImate $cli = null, string $last_file = ''): string
    {
        $code = $this->getFile($filename, $last_file);
        if (substr($filename, 0, 3) == '**$') {
            $filename = getcwd();
        }
        $lines = explode("\n", $code);

        if ($cli !== null) {
            $cli->out("<green><bold>Input file: </bold></green> <underline>$filename</underline>");
        }

        foreach ($lines as &$line) {
            $line = trim($line);
            if ($line == '') continue;
            if (substr($line, 0, 18) === '#import_directory ') {
                $dir = substr($line, 18, 0);
                $this->addImportDirectory($dir);
                continue;
            }
            if (substr($line, 0, 8) !== '!import ') {
                break;
            }
            $file = trim(substr($line, 8));
            $line = $this->mergeFile($file, $cli, $filename);
        }

        return implode("\n", $lines);
    }

    public static function getLineNumber(string $code): int
    {
        return count(explode("\n", $code));
    }

    protected function getFile(string $filename, string $current_file = ''): string
    {
        if (substr($filename, 0, 3) == '**$') {
            return substr($filename, 3);
        }
        foreach ($this->importDirectories as $dir) {
            if (file_exists($dir . '/' . $filename)) {
                return file_get_contents($dir . '/' . $filename);
            }
        }
        if (!file_exists(dirname($current_file) . '/' . $filename)) {
            throw new \Exception("<red><bold>Import file not found:</bold></red> $filename");
        }
        return file_get_contents(dirname($current_file) . '/' . $filename);
    }
}
