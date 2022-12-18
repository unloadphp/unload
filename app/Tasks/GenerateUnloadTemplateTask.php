<?php

namespace App\Tasks;

use App\Configs\BootstrapConfig;
use App\Templates\UnloadTemplate;

class GenerateUnloadTemplateTask
{
    private BootstrapConfig $config;

    public function __construct(BootstrapConfig $config)
    {
        $this->config = $config;
    }

    public function handle(): void
    {
        $unloadTemplate = new UnloadTemplate($this->config);
        $unloadTemplate->make();
    }
}
