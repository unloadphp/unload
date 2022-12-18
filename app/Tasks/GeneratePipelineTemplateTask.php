<?php

namespace App\Tasks;

use App\Aws\ContinuousIntegration;
use App\Configs\BootstrapConfig;
use App\Templates\SamPipelineTemplate;
use App\Templates\UnloadTemplate;

class GeneratePipelineTemplateTask
{
    private BootstrapConfig $config;

    public function __construct(BootstrapConfig $config)
    {
        $this->config = $config;
    }

    public function handle(): void
    {
        $ci = new ContinuousIntegration($this->config);
        $pipelineTemplate = new SamPipelineTemplate($this->config, $ci);
        $pipelineTemplate->make();
    }
}
