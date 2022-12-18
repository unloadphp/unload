<?php

namespace App\Commands;

use App\Configs\BootstrapConfig;
use App\Task;
use App\Tasks\InitContinuousIntegrationTask;

class CiCommand extends Command
{
    protected $signature = 'ci {--region=} {--profile=} {--env=} {--app=} {--provider=} {--repository= : The name of the github repository. Example: username/repo-name}';
    protected $description = 'Deploy or update application stack for continuous integration';

    public function handle()
    {
        if (!$this->option('repository')) {
            $this->error('Invalid github repository. Example: username/myrepo');
            return;
        }

        $bootstrap = new BootstrapConfig($this->options());
        Task::chain([new InitContinuousIntegrationTask($bootstrap)])->execute($this);

        $this->newLine();
        $this->line("Continuous integration for {$bootstrap->env()} has been configured.");
    }
}
