<?php

namespace Tests\Commands;

use App\System;
use Tests\TestCase;

class DashboardCommandTest extends TestCase
{
    public function test_can_generate_and_open_dashboard_url()
    {
        $system = $this->mock(System::class);
        $configuration = base_path('tests/Fixtures/unload.yaml');

        $system->shouldReceive('browser')->withArgs(function ($url) {
            return str_starts_with(
                urldecode($url),
                'https://signin.aws.amazon.com/federation?Action=login&Destination=https://us-east-1.console.aws.amazon.com/lambda/home?region=us-east-1#/applications/unload-production-sample-app?tab=monitoring&SigninToken=');
        })->andReturnFalse();

        $this->artisan('dashboard', ['--config' => $configuration])
            ->expectsOutputToContain('Generating dashboard for the application')
            ->assertSuccessful()
            ->execute();
    }
}
