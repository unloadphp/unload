<?php

namespace App\Commands;

use App\Aws\ContinuousIntegration;
use App\Task;
use App\Tasks\ExecuteSamDeployTask;
use App\Tasks\GenerateSamConfigTask;
use App\Tasks\InitCertificateTask;
use App\Tasks\UploadAssetTask;

class DeployCommand extends Command
{
    protected $signature = 'deploy';
    protected $description = 'Deploy application configuration and artifacts';

    public function handle(ContinuousIntegration $ci)
    {
        $this->call('build');

        $this->newLine();
        $this->info("Deploying {$this->unload->app()} project to the {$this->unload->accountId()} ({$this->unload->profile()}) account");

        Task::chain($ci->applicationStackExists() ? [
            new InitCertificateTask(),
            new GenerateSamConfigTask(),
            new UploadAssetTask(),
            new ExecuteSamDeployTask(),
        ] : [
            new InitCertificateTask(),
            new GenerateSamConfigTask(),
            new ExecuteSamDeployTask(),
            new UploadAssetTask(),
        ])->execute($this);
    }
}
