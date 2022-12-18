<?php

namespace App\Commands;

use App\Configs\BootstrapConfig;
use App\Task;
use App\Tasks\InitNetworkTask;

class NetworkCommand extends Command
{
    protected $signature = 'network {--vpc=} {--nat=} {--region=} {--profile=} {--env=} {--app=}';
    protected $description = 'Deploy or update environment network stack';

    public function handle(): void
    {
        $boostrap = new BootstrapConfig($this->options());

        $this->newLine();
        $this->info("Running network deployment for {$boostrap->env()} environment");

        Task::chain([new InitNetworkTask($boostrap)])->execute($this);
    }
}
