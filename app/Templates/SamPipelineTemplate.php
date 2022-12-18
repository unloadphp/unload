<?php

namespace App\Templates;

use App\Aws\ContinuousIntegration;
use App\Configs\BootstrapConfig;
use App\Configs\UnloadConfig;
use App\Path;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

class SamPipelineTemplate extends Template
{
    protected UnloadConfig|BootstrapConfig $unloadConfig;
    private ContinuousIntegration $ci;

    public function __construct(BootstrapConfig $unloadConfig, ContinuousIntegration $ci)
    {
        $this->ci = $ci;
        parent::__construct($unloadConfig);
    }

    public function make(): bool
    {
        $pipelineConfigPath = Path::tmpSamPipelineConfig();
        $pipelineDirectory = File::dirname($pipelineConfigPath);
        if (!File::exists($pipelineDirectory)) {
            File::makeDirectory($pipelineDirectory, 0777, true);
            File::put($pipelineConfigPath, 'version = 0.1' . PHP_EOL);
        }

        $stage = $this->unloadConfig->env();
        $pipelineConfiguration = "[$stage]" . PHP_EOL;
        $pipelineConfiguration .= "[$stage.pipeline_bootstrap]" . PHP_EOL;
        $pipelineConfiguration .= "[$stage.pipeline_bootstrap.parameters]" . PHP_EOL;
        $pipelineConfiguration .= sprintf('%s_region = "%s"', $stage, $this->unloadConfig->region()) . PHP_EOL;
        $pipelineConfiguration .= sprintf('%s_git_branch = "%s"', $stage, $this->unloadConfig->branch()) . PHP_EOL;
        $pipelineConfiguration .= sprintf('%s_pipeline_execution_role = "%s"', $stage, $this->ci->getPipelineExecutionRoleArn()) . PHP_EOL;
        $pipelineConfiguration .= sprintf('%s_unload_template = "%s"', $stage, $this->unloadConfig->template(relative: true)) . PHP_EOL;
        $pipelineConfiguration .= sprintf('%s_unload_version = "%s"', $stage, App::version()) . PHP_EOL;
        $pipelineConfiguration .= sprintf('%s_php_version = "%s"', $stage, $this->unloadConfig->php()) . PHP_EOL;

        return File::append($pipelineConfigPath, $pipelineConfiguration);
    }
}
