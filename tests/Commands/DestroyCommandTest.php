<?php

namespace Tests\Commands;

use App\Tasks\DestroyContinuousIntegrationTask;
use App\Tasks\DestroyNetworkTask;
use App\Tasks\ExecuteSamDeleteTask;
use App\Tasks\FlushEnvironmentTask;
use App\Tasks\GeneratePipelineTask;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class DestroyCommandTest extends TestCase
{
    public function test_destroy_is_not_being_requested_if_not_confirmed()
    {
        App::shouldReceive('call')->never()->andReturnFalse()->because(GeneratePipelineTask::class);
        App::shouldReceive('call')->never()->andReturnFalse()->because(FlushEnvironmentTask::class);
        App::shouldReceive('call')->never()->andReturnFalse()->because(DestroyContinuousIntegrationTask::class);
        App::shouldReceive('call')->never()->andReturnFalse()->because(DestroyNetworkTask::class);
        App::partialMock();

        $this->artisan('destroy', [
                '--env' => 'production'
            ])
            ->expectsConfirmation('Are you sure you want to processed?', 'no')
            ->execute();
    }

    public function test_destroy_is_requested_after_confirmation()
    {
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof ExecuteSamDeleteTask)->once()->andReturnFalse()->because(ExecuteSamDeleteTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof FlushEnvironmentTask)->once()->andReturnFalse()->because(FlushEnvironmentTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof DestroyContinuousIntegrationTask)->once()->andReturnFalse()->because(DestroyContinuousIntegrationTask::class);
        App::shouldReceive('call')->withArgs(fn($args) => $args[0] instanceof DestroyNetworkTask)->once()->andReturnFalse()->because(DestroyNetworkTask::class);
        App::partialMock();

        $this->artisan('destroy', [
            '--env' => 'production'
        ])
            ->expectsConfirmation('Are you sure you want to processed?', 'yes')
            ->execute();
    }
}
