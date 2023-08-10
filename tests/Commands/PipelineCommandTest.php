<?php

namespace Tests\Commands;

use App\Configs\BootstrapConfig;
use App\Tasks\GeneratePipelineTask;
use App\Tasks\InitNetworkTask;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class PipelineCommandTest extends TestCase
{
    public function test_can_request_pipeline_creation()
    {
        App::shouldReceive('call')->withArgs(function($args) {
                if (!$args[0] instanceof GeneratePipelineTask) {
                    return false;
                }

                $reflectedClass = new \ReflectionClass($args[0]);

                $stages = $reflectedClass->getProperty('stages');
                $stages->setAccessible(true);

                $provider = $reflectedClass->getProperty('provider');
                $provider->setAccessible(true);

                $defintion = $reflectedClass->getProperty('definition');
                $defintion->setAccessible(true);

                return $stages->getValue($args[0]) == 2
                    && $provider->getValue($args[0]) == 'github'
                    && $defintion->getValue($args[0]) == '/test/path';
            })
            ->once()
            ->andReturnFalse()
            ->because(GeneratePipelineTask::class);
        App::partialMock();

        $this->artisan('pipeline', [
                '--stages' => 2,
                '--provider' => 'github',
                '--definition' => '/test/path',
            ])
            ->expectsOutputToContain("Generating github continuous integration pipeline for 2 stage deployment")
            ->assertSuccessful()
            ->execute();
    }
}
