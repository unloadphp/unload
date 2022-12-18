<?php

namespace App\Commands;

use App\Task;
use App\Tasks\DestroyContinuousIntegrationTask;
use App\Tasks\ExecuteSamDeleteTask;
use App\Tasks\FlushEnvironmentTask;

class DestroyCommand extends Command
{
    protected $signature = 'destroy {--env=}';
    protected $description = 'Destroy provisioned application resources and artifacts';

    public function handle(): void
    {
        $this->alert('This is dangerous operation!!!');
        $this->comment("1) It will remove the application stack for {$this->unload->env()} environment");
        $this->comment("2) It will remove the application variables in {$this->unload->env()} environment");
        $this->comment("3) It will remove the continuous integration stack for {$this->unload->env()} environment");

        if (!$this->confirm('Are you sure you want to processed?')) {
            return;
        }

        Task::chain([
                new ExecuteSamDeleteTask(),
                new FlushEnvironmentTask(),
                new DestroyContinuousIntegrationTask(),
            ])
            ->execute($this);
    }
}
