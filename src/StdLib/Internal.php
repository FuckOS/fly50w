<?php

namespace Fly50w\StdLib;

use Fly50w\VM\VM;
use Symfony\Component\VarDumper\VarDumper;

class Internal extends LibraryBase
{
    public function init(VM $vm)
    {
        $vm->assignNativeFunction('strlen', 'strlen');
        $vm->assignNativeFunction('count', 'count');
        $vm->assignNativeFunction('sin', 'sin');
        $vm->assignNativeFunction('cos', 'cos');
        $vm->assignNativeFunction('tan', 'tan');
        $vm->assignNativeFunction('string_to_array', 'str_split');
    }

    #[FunctionName('assert')]
    public function assert(array $args, VM $vm)
    {
        foreach ($args as $arg) {
            if (!$arg) {
                return $vm->throwError('assertError');
            }
        }
        return true;
    }

    #[FunctionName('print')]
    public function print(array $args, VM $vm)
    {
        foreach ($args as $arg) {
            echo $arg;
        }
        return $args[0];
    }

    #[FunctionName('debug')]
    public function debug(array $args, VM $vm)
    {
        foreach ($args as $arg) {
            VarDumper::dump($arg);
        }
        return $args[0];
    }

    #[FunctionName('array')]
    public function consArray(array $args, VM $vm)
    {
        return $args;
    }

    #[FunctionName('merge')]
    public function mergeArray(array $args, VM $vm)
    {
        $rslt = [];
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $rslt = array_merge($rslt, $arg);
            } else {
                $rslt[] = $arg;
            }
        }
        return $rslt;
    }

    #[FunctionName('read_file')]
    public function readFile(array $args, VM $vm)
    {
        if (count($args) != 1) {
            return $vm->throwError('wrongArgumentNumber');
        }
        $f = $args[0];
        if (!file_exists($f)) {
            return $vm->throwError('fileNotExists');
        }
        return file_get_contents($f);
    }

    #[FunctionName('write_file')]
    public function writeFile(array $args, VM $vm)
    {
        if (count($args) != 2) {
            return $vm->throwError('wrongArgumentNumber');
        }
        $f = $args[0];
        $d = $args[1];
        $append = isset($args[2]) ? false : $args[2];
        $r = file_put_contents($f, $d, $append ? FILE_APPEND : 0);
        if (!$r) {
            return $vm->throwError('fileWriteFailed');
        }
        return true;
    }
}
