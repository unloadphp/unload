<?php

namespace App\Templates;

use App\Configs\UnloadConfig;

abstract class Template
{
    protected UnloadConfig $unloadConfig;

    public function __construct(UnloadConfig $unloadConfig)
    {
        $this->unloadConfig = $unloadConfig;
    }

    public abstract function make(): bool;
}
