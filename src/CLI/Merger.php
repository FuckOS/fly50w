<?php

namespace Fly50w\CLI;

use League\CLImate\CLImate;

class Merger
{
    public static function mergeFile(string $filename, ?CLImate $cli = null): string
    {
        $code = self::getFile($filename);
        $lines = explode("\n", $code);

        if ($cli !== null) {
            $cli->out("<green><bold>Input file: </bold></green> <underline>$filename</underline>");
        }

        foreach ($lines as &$line) {
            $line = trim($line);
            if ($line == '') continue;
            if (substr($line, 0, 8) != '!import ') {
                break;
            }
            $file = trim(substr($line, 8));
            $line = self::mergeFile(dirname($filename) . '/' . $file, $cli);
        }

        return implode("\n", $lines);
    }

    public static function getLineNumber(string $code): int
    {
        return count(explode("\n", $code));
    }

    protected static function getFile(string $filename): string
    {
        if (substr($filename, 0, 3) == '**$') {
            return substr($filename, 3);
        }
        if (!file_exists($filename)) {
            throw new \Exception("<red><bold>Import file not found:</bold></red> $filename");
        }
        return file_get_contents($filename);
    }
}
