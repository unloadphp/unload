<?php

namespace Tests\Commands;

use App\Aws\SystemManager;
use App\Configs\UnloadConfig;
use App\System;
use Tests\TestCase;

class EnvCommandTest extends TestCase
{
    public function test_environment_can_be_updated_via_system_editor()
    {
        $system = $this->mock(System::class);
        $configuration = base_path('tests/Fixtures/unload.yaml');
        $key = base64_encode(random_bytes(32));

        $systemManager = new SystemManager(UnloadConfig::fromPath($configuration));
        $systemManager->putCiParameter('key', $key);
        $systemManager->putEnvironment('TEST=123');

        $this->assertEquals($systemManager->fetchEnvironment(true), 'TEST=123');
        $system->shouldReceive('open')->with('TEST=123')->andReturn('TEST=444');

        $this->artisan('env', ['--config' => $configuration])
            ->expectsOutputToContain('Saving environment configuration to system manager')
            ->expectsOutputToContain('SSM Path: /sample/production')
            ->assertSuccessful()
            ->execute();

        $this->assertEquals($systemManager->fetchEnvironment(true), 'TEST=444');
    }

    public function test_environment_encryption_key_can_be_rotated()
    {
        $system = $this->mock(System::class);
        $configuration = base_path('tests/Fixtures/unload.yaml');
        $key = base64_encode(random_bytes(32));

        $systemManager = new SystemManager(UnloadConfig::fromPath($configuration));
        $systemManager->putCiParameter('key', $key);
        $systemManager->putEnvironment('TEST=555');
        $environment = $systemManager->fetchEnvironment();

        $system->shouldReceive('open')->with('TEST=555')->andReturn('TEST=555');

        $this->artisan('env', ['--config' => $configuration, '--rotate' => true])
            ->expectsOutputToContain('Saving environment configuration to system manager')
            ->expectsOutputToContain('SSM Path: /sample/production')
            ->assertSuccessful()
            ->execute();

        $this->assertNotEquals($environment, $systemManager->fetchEnvironment());
        $this->assertEquals('TEST=555', $systemManager->fetchEnvironment(true));
    }
}
