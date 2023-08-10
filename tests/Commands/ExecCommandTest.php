<?php

namespace Tests\Commands;

use App\Configs\BootstrapConfig;
use App\Tasks\InitNetworkTask;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class ExecCommandTest extends TestCase
{
    public function test_can_request_network_creation()
    {
        App::shouldReceive('call')->withArgs(function($args) {
                if (!$args[0] instanceof InitNetworkTask) {
                    return false;
                }

                $reflectedClass = new \ReflectionClass($args[0]);
                $reflection = $reflectedClass->getProperty('config');
                $reflection->setAccessible(true);

                /** @var BootstrapConfig $config */
                $config = $reflection->getValue($args[0]);

                return $config->region() == 'ca-central-1'
                    && $config->vpc() == '2az'
                    && $config->nat() == 'gateway'
                    && $config->profile() == 'unload'
                    && $config->env() == 'development'
                    && $config->app() == 'sample'
                    && $config->ssh() == false;
            })
            ->once()
            ->andReturnFalse()
            ->because(InitNetworkTask::class);
        App::partialMock();

        $this->artisan('network', [
                '--vpc' => '2az',
                '--nat' => 'gateway',
                '--region' => 'ca-central-1',
                '--profile' => 'unload',
                '--env' => 'development',
                '--app' => 'sample',
                '--ssh' => 0,
            ])
            ->expectsOutputToContain('Running network deployment for development environment')
            ->assertSuccessful()
            ->execute();
    }
}
