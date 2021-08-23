<?php

namespace Fly50w\StdLib;

use Fly50w\VM\VM;
use ReflectionClass;

abstract class LibraryBase
{
    public function __construct(
        VM $vm
    ) {
        $reflect = new ReflectionClass($this);
        foreach ($reflect->getMethods() as $method) {
            if ($method->getName() === 'init') {
                call_user_func([$this, 'init'], $vm);
                continue;
            }
            $attrs = $method->getAttributes(FunctionName::class);
            foreach ($attrs as $attr) {
                $name = $attr->newInstance()->name;
                $vm->assignVariable($name, [$this, $method->getName()]);
            }
        }
    }
}
