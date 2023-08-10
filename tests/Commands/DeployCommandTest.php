<?php

namespace Tests\Commands;

use App\Aws\ContinuousIntegration;
use App\Tasks\CleanupFilesTask;
use App\Tasks\CleanupVendorTask;
use App\Tasks\CopyFormationToBuildDirectoryTask;
use App\Tasks\CopySourceToBuildDirectoryTask;
use App\Tasks\DestroyComposerPlatformCheckTask;
use App\Tasks\EmptyAwsCredentialTask;
use App\Tasks\ExecuteBuildTask;
use App\Tasks\ExecuteSamBuildTask;
use App\Tasks\ExecuteSamDeployTask;
use App\Tasks\ExtractStaticAssetsTask;
use App\Tasks\GenerateMakefileTask;
use App\Tasks\GenerateSamConfigTask;
use App\Tasks\GenerateSamTemplateTask;
use App\Tasks\InitCertificateTask;
use App\Tasks\PrepareBuildDirectoryTask;
use App\Tasks\SetupEnvFileTask;
use App\Tasks\SetupPhpIniFileTask;
use App\Tasks\UploadAssetTask;
use Aws\Result;
use Aws\Sts\StsClient;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class DeployCommandTest extends TestCase
{
    public function test_can_request_deployment_for_new_application()
    {
        $stsClient = \Mockery::mock(StsClient::class)
            ->shouldReceive('getCallerIdentity')
            ->once()
            ->andReturn(new Result(['Account' => '11111']))
            ->getMock();
        $continiousIntegration = \Mockery::mock(ContinuousIntegration::class)
            ->shouldReceive('applicationStackExists')
            ->once()
            ->andReturnFalse()
            ->getMock();

        $this->app->instance(ContinuousIntegration::class, $continiousIntegration);

        // mock response for account id
        App::shouldReceive('make')->zeroOrMoreTimes()->andReturn($stsClient)->because(StsClient::class);

        // part of build command
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof PrepareBuildDirectoryTask)->once()->andReturnFalse()->because(PrepareBuildDirectoryTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof CopyFormationToBuildDirectoryTask)->once()->andReturnFalse()->because(CopyFormationToBuildDirectoryTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof CopySourceToBuildDirectoryTask)->once()->andReturnFalse()->because(CopySourceToBuildDirectoryTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof GenerateMakefileTask)->once()->andReturnFalse()->because(GenerateMakefileTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof ExecuteBuildTask)->once()->andReturnFalse()->because(ExecuteBuildTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof DestroyComposerPlatformCheckTask)->once()->andReturnFalse()->because(DestroyComposerPlatformCheckTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof ExtractStaticAssetsTask)->once()->andReturnFalse()->because(ExtractStaticAssetsTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof CleanupFilesTask)->once()->andReturnFalse()->because(CleanupFilesTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof CleanupVendorTask)->once()->andReturnFalse()->because(CleanupVendorTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof EmptyAwsCredentialTask)->once()->andReturnFalse()->because(EmptyAwsCredentialTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof SetupEnvFileTask)->once()->andReturnFalse()->because(SetupEnvFileTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof SetupPhpIniFileTask)->once()->andReturnFalse()->because(SetupPhpIniFileTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof GenerateSamTemplateTask)->once()->andReturnFalse()->because(GenerateSamTemplateTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof ExecuteSamBuildTask)->once()->andReturnFalse()->because(ExecuteSamBuildTask::class);

        // part of deployment process
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof InitCertificateTask)->once()->andReturnFalse()->because(InitCertificateTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof GenerateSamConfigTask)->once()->andReturnFalse()->because(GenerateSamConfigTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof UploadAssetTask)->once()->andReturnFalse()->because(UploadAssetTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof ExecuteSamDeployTask)->once()->andReturnFalse()->because(ExecuteSamDeployTask::class);

        App::partialMock();

        $configuration = base_path('tests/Fixtures/unload.yaml');

        $this->artisan('deploy', [
            '--config' => $configuration
        ])
            ->expectsOutputToContain("Building sample project for production environment")
            ->expectsOutputToContain("Deploying sample project to the 11111 (default) account")
            ->execute();
    }

    public function test_can_request_deployment_for_existing_application()
    {
        $called = [];
        $stsClient = \Mockery::mock(StsClient::class)
            ->shouldReceive('getCallerIdentity')
            ->once()
            ->andReturn(new Result(['Account' => '11111']))
            ->getMock();
        $continiousIntegration = \Mockery::mock(ContinuousIntegration::class)
            ->shouldReceive('applicationStackExists')
            ->once()
            ->andReturnTrue()
            ->getMock();

        $this->app->instance(ContinuousIntegration::class, $continiousIntegration);

        // mock response for account id
        App::shouldReceive('make')->zeroOrMoreTimes()->andReturn($stsClient)->because(StsClient::class);

        // part of build command
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof PrepareBuildDirectoryTask)->once()->andReturnFalse()->because(PrepareBuildDirectoryTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof CopyFormationToBuildDirectoryTask)->once()->andReturnFalse()->because(CopyFormationToBuildDirectoryTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof CopySourceToBuildDirectoryTask)->once()->andReturnFalse()->because(CopySourceToBuildDirectoryTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof GenerateMakefileTask)->once()->andReturnFalse()->because(GenerateMakefileTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof ExecuteBuildTask)->once()->andReturnFalse()->because(ExecuteBuildTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof DestroyComposerPlatformCheckTask)->once()->andReturnFalse()->because(DestroyComposerPlatformCheckTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof ExtractStaticAssetsTask)->once()->andReturnFalse()->because(ExtractStaticAssetsTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof CleanupFilesTask)->once()->andReturnFalse()->because(CleanupFilesTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof CleanupVendorTask)->once()->andReturnFalse()->because(CleanupVendorTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof EmptyAwsCredentialTask)->once()->andReturnFalse()->because(EmptyAwsCredentialTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof SetupEnvFileTask)->once()->andReturnFalse()->because(SetupEnvFileTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof SetupPhpIniFileTask)->once()->andReturnFalse()->because(SetupPhpIniFileTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof GenerateSamTemplateTask)->once()->andReturnFalse()->because(GenerateSamTemplateTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof ExecuteSamBuildTask)->once()->andReturnFalse()->because(ExecuteSamBuildTask::class);

        // part of deployment process

        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof UploadAssetTask && $called[] = UploadAssetTask::class)->once()->andReturnFalse()->because(UploadAssetTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof InitCertificateTask && $called[] = InitCertificateTask::class)->once()->andReturnFalse()->because(InitCertificateTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof GenerateSamConfigTask && $called[] = GenerateSamConfigTask::class)->once()->andReturnFalse()->because(GenerateSamConfigTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof ExecuteSamDeployTask && $called[] = ExecuteSamDeployTask::class)->once()->andReturnFalse()->because(ExecuteSamDeployTask::class);

        App::partialMock();

        $configuration = base_path('tests/Fixtures/unload.yaml');

        $this->artisan('deploy', [
            '--config' => $configuration
        ])
            ->expectsOutputToContain("Building sample project for production environment")
            ->expectsOutputToContain("Deploying sample project to the 11111 (default) account")
            ->execute();
    }
}
