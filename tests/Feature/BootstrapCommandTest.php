<?php

namespace Tests\Feature;

use App\Tasks\CleanupPipelineConfigTask;
use App\Tasks\GeneratePipelineTask;
use App\Tasks\GeneratePipelineTemplateTask;
use App\Tasks\GenerateUnloadTemplateTask;
use App\Tasks\InitContinuousIntegrationTask;
use App\Tasks\InitEnvironmentTask;
use App\Tasks\InitNetworkTask;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class BootstrapCommandTest extends TestCase
{
    public function test_bootstrap_fails_with_missing_profile()
    {
        $this->artisan('bootstrap')
            ->expectsQuestion('App name (example: sample)', 'sample-app')
            ->expectsQuestion('App php version', '8.1')
            ->expectsQuestion('App environment', 'production')
            ->expectsQuestion('App repository provider', 'github')
            ->expectsQuestion('App repository path (example: username/app-name)', 'sample/app')
            ->expectsQuestion('Production aws region', 'us-east-1')
            ->expectsQuestion('Production github branch', 'master')
            ->expectsQuestion('Production aws profile', 'master')
            ->expectsOutputToContain('Profile master not found at ~/.aws/credentials. Please create one and then retry.')
            ->execute();
    }

    public function test_bootstrap_correctly_builds_a_list_of_commands_to_provision_single_environment()
    {
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof CleanupPipelineConfigTask)->twice()->andReturnFalse()->because(CleanupPipelineConfigTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof GenerateUnloadTemplateTask)->once()->andReturnFalse()->because(GenerateUnloadTemplateTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof InitContinuousIntegrationTask)->once()->andReturnFalse()->because(InitContinuousIntegrationTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof InitNetworkTask)->once()->andReturnFalse()->because(InitNetworkTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof InitEnvironmentTask)->once()->andReturnFalse()->because(InitEnvironmentTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof GeneratePipelineTemplateTask)->once()->andReturnFalse()->because(GeneratePipelineTemplateTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof GeneratePipelineTask)->once()->andReturnFalse()->because(GeneratePipelineTask::class);
        App::partialMock();

        $this->artisan('bootstrap')
            ->expectsQuestion('App name (example: sample)', 'sample-app')
            ->expectsQuestion('App php version', '8.1')
            ->expectsQuestion('App environment', 'production')
            ->expectsQuestion('App repository provider', 'github')
            ->expectsQuestion('App repository path (example: username/app-name)', 'sample/app')
            ->expectsQuestion('Production aws region', 'us-east-1')
            ->expectsQuestion('Production github branch', 'master')
            ->expectsQuestion('Production aws profile', 'unload')
            ->expectsQuestion('Production aws vpc size', '1az')
            ->expectsQuestion('Production aws nat type', 'instance')
            ->expectsQuestion('Production aws vpc ssh access (allow database connection from internet)', 'yes')
            ->assertSuccessful()
            ->execute();
    }

    public function test_bootstrap_builds_a_list_of_commands_to_provision_tow_environment()
    {
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof CleanupPipelineConfigTask)->twice()->andReturnFalse()->because(CleanupPipelineConfigTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof GenerateUnloadTemplateTask)->twice()->andReturnFalse()->because(GenerateUnloadTemplateTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof InitContinuousIntegrationTask)->twice()->andReturnFalse()->because(InitContinuousIntegrationTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof InitNetworkTask)->twice()->andReturnFalse()->because(InitNetworkTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof InitEnvironmentTask)->twice()->andReturnFalse()->because(InitEnvironmentTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof GeneratePipelineTemplateTask)->twice()->andReturnFalse()->because(GeneratePipelineTemplateTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof GeneratePipelineTask)->once()->andReturnFalse()->because(GeneratePipelineTask::class);
        App::partialMock();

        $this->artisan('bootstrap')
            ->expectsQuestion('App name (example: sample)', 'sample-app')
            ->expectsQuestion('App php version', '8.1')
            ->expectsQuestion('App environment', 'production/development')
            ->expectsQuestion('App repository provider', 'github')
            ->expectsQuestion('App repository path (example: username/app-name)', 'sample/app')

            ->expectsQuestion('Production aws region', 'us-east-1')
            ->expectsQuestion('Production github branch', 'master')
            ->expectsQuestion('Production aws profile', 'unload')
            ->expectsQuestion('Production aws vpc size', '1az')
            ->expectsQuestion('Production aws nat type', 'instance')
            ->expectsQuestion('Production aws vpc ssh access (allow database connection from internet)', 'yes')

            ->expectsQuestion('Development aws region', 'us-east-1')
            ->expectsQuestion('Development github branch', 'master')
            ->expectsQuestion('Development aws profile', 'unload')
            ->expectsQuestion('Development aws vpc size', '1az')
            ->expectsQuestion('Development aws nat type', 'instance')
            ->expectsQuestion('Development aws vpc ssh access (allow database connection from internet)', 'yes')
            ->assertSuccessful()
            ->execute();
    }
}
