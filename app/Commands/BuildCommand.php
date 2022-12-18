<?php

namespace App\Commands;

use App\Task;
use App\Tasks\CleanupFilesTask;
use App\Tasks\CleanupVendorTask;
use App\Tasks\CopyFormationToBuildDirectoryTask;
use App\Tasks\CopySourceToBuildDirectoryTask;
use App\Tasks\DestroyComposerPlatformCheckTask;
use App\Tasks\EmptyAwsCredentialTask;
use App\Tasks\ExecuteBuildTask;
use App\Tasks\ExecuteSamBuildTask;
use App\Tasks\ExtractStaticAssetsTask;
use App\Tasks\GenerateMakefileTask;
use App\Tasks\GenerateSamConfigTask;
use App\Tasks\GenerateSamTemplateTask;
use App\Tasks\PrepareBuildDirectoryTask;
use App\Tasks\SetupEnvFileTask;

class BuildCommand extends Command
{
    protected $signature = 'build';
    protected $description = 'Build a serverless application package';

    public function handle()
    {
        $this->newLine();
        $this->info("Building {$this->unload->app()} project for {$this->unload->env()} environment");

        Task::chain([
            new PrepareBuildDirectoryTask(),
            new CopyFormationToBuildDirectoryTask(),
            new CopySourceToBuildDirectoryTask(),
            new GenerateMakefileTask(),
            new ExecuteBuildTask(),
            new DestroyComposerPlatformCheckTask(),
            new ExtractStaticAssetsTask(),
            new CleanupFilesTask(),
            new CleanupVendorTask(),
            new EmptyAwsCredentialTask(),
            new SetupEnvFileTask(),
            new GenerateSamTemplateTask(),
            new ExecuteSamBuildTask(),
        ])->execute($this);
    }
}
