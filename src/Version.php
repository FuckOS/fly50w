<?php

/**
 * Copyright Fly50w 2021
 * 
 * @author Tianle Xu <xtl@xtlsoft.top>
 * @license Apache 2
 */

namespace Fly50w;

class Version
{
    /**
     * {@inheritDoc}
     */
    public static function getVersion(): string
    {
        return "v0.1.0";
    }

    public static function getArt(): string
    {
        return <<<ART
.   ________      __________          
   / ____/ /_  __/ ____/ __ \_      __
  / /_  / / / / /___ \/ / / / | /| / /
 / __/ / / /_/ /___/ / /_/ /| |/ |/ / 
/_/   /_/\__, /_____/\____/ |__/|__/  
        /____/  
ART;
    }
}
