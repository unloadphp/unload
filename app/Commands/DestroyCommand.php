<?php

namespace App\Commands;

use App\Task;
use App\Tasks\DestroyContinuousIntegrationTask;
use App\Tasks\DestroyNetworkTask;
use App\Tasks\ExecuteSamDeleteTask;
use App\Tasks\FlushEnvironmentTask;

class DestroyCommand extends Command
{
    protected $signature = 'destroy {--force}';
    protected $description = 'Destroy provisioned application resources and artifacts';

    public function handle(): void
    {
        $this->alert('This is dangerous operation!!!');
        $this->comment("1) It will remove the application stack for ({$this->unload->appStackName()}) in {$this->unload->env()} environment.");
        $this->comment("2) It will remove the application variables ({$this->unload->ssmPath()}) in {$this->unload->env()} environment.");
        $this->comment("3) It will remove the continuous integration stack ({$this->unload->ciStackName()}) in {$this->unload->env()} environment.");
        $this->comment("4) It will remove the network stack ({$this->unload->networkStackName()}), in case it is the only dependant application.");

        if (!$this->option('force') && !$this->confirm('Are you sure you want to processed?')) {
            return;
        }

        Task::chain([
                new ExecuteSamDeleteTask(),
                new FlushEnvironmentTask(),
                new DestroyContinuousIntegrationTask(),
                new DestroyNetworkTask(),
            ])
            ->execute($this);
    }
}
