<?php

namespace Tests\Commands;

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
use App\Tasks\GenerateSamTemplateTask;
use App\Tasks\PrepareBuildDirectoryTask;
use App\Tasks\SetupEnvFileTask;
use App\Tasks\SetupPhpIniFileTask;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class BuildCommandTest extends TestCase
{
    public function test_can_request_project_build()
    {
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof PrepareBuildDirectoryTask)->once()->andReturnFalse()->because(PrepareBuildDirectoryTask::class);;
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof CopyFormationToBuildDirectoryTask)->once()->andReturnFalse()->because(CopyFormationToBuildDirectoryTask::class);;
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof CopySourceToBuildDirectoryTask)->once()->andReturnFalse()->because(CopySourceToBuildDirectoryTask::class);;
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof GenerateMakefileTask)->once()->andReturnFalse()->because(GenerateMakefileTask::class);;
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof ExecuteBuildTask)->once()->andReturnFalse()->because(ExecuteBuildTask::class);;
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof DestroyComposerPlatformCheckTask)->once()->andReturnFalse()->because(DestroyComposerPlatformCheckTask::class);;
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof ExtractStaticAssetsTask)->once()->andReturnFalse()->because(ExtractStaticAssetsTask::class);;
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof CleanupFilesTask)->once()->andReturnFalse()->because(CleanupFilesTask::class);;
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof CleanupVendorTask)->once()->andReturnFalse()->because(CleanupVendorTask::class);;
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof EmptyAwsCredentialTask)->once()->andReturnFalse()->because(EmptyAwsCredentialTask::class);;
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof SetupEnvFileTask)->once()->andReturnFalse()->because(SetupEnvFileTask::class);;
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof SetupPhpIniFileTask)->once()->andReturnFalse()->because(SetupPhpIniFileTask::class);;
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof GenerateSamTemplateTask)->once()->andReturnFalse()->because(GenerateSamTemplateTask::class);;
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof ExecuteSamBuildTask)->once()->andReturnFalse()->because(ExecuteSamBuildTask::class);;
        App::partialMock();

        $configuration = base_path('tests/Fixtures/unload.yaml');

        $this->artisan('build', [
                '--config' => $configuration
            ])
            ->expectsOutputToContain("Building sample project for production environment")
            ->execute();
    }
}
