<?php

namespace Tests\Commands;

use App\Configs\BootstrapConfig;
use App\Tasks\InitContinuousIntegrationTask;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class CiCommandTest extends TestCase
{
    public function test_fails_without_repository_parameter()
    {
        $this->artisan('ci')
            ->expectsOutputToContain('Invalid github repository. Example: username/myrepo')
            ->assertExitCode(0)
            ->execute();
    }

    public function test_can_request_ci_stack_creation()
    {
        App::shouldReceive('call')->withArgs(function($args) {
            if (!$args[0] instanceof InitContinuousIntegrationTask) {
                return false;
            }

            $reflectedClass = new \ReflectionClass($args[0]);
            $reflection = $reflectedClass->getProperty('config');
            $reflection->setAccessible(true);

            /** @var BootstrapConfig $config */
            $config = $reflection->getValue($args[0]);

            return $config->region() == 'eu-east-1'
                && $config->provider() == 'github'
                && $config->env() == 'staging'
                && $config->repository() == 'example/test';
        })
            ->once()
            ->andReturnFalse()
            ->because(InitContinuousIntegrationTask::class);
        App::partialMock();

        $this->artisan('ci', [
                '--env' => 'staging',
                '--provider' => 'github',
                '--region' => 'eu-east-1',
                '--repository' => 'example/test'
            ])
            ->expectsOutputToContain("Continuous integration for staging has been configured.")
            ->assertSuccessful()
            ->execute();
    }
}
