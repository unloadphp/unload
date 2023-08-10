<?php

namespace Tests\Integration;

use Tests\TestCase;

class DeploymentTest extends TestCase
{
    public function test_can_bootstrap_and_deploy_sample_application()
    {
        $this->setupTempDirectory();

        exec('composer create-project laravel/laravel .');

        $this->setupLocalPackageRepository();

        exec('composer require unload/unload-laravel');

        exec('../../../unload bootstrap --app=test --php=8.1 --env=production --provider=github --profile=integration --repository=test/example --no-interaction', $output);
        dd($output);
    }

    protected function setupTempDirectory(): void
    {
        $tempDir = base_path('tests/Fixtures/tmp');
        exec('rm -rf '.$tempDir);
        exec('mkdir -p '.$tempDir);
        chdir($tempDir);
    }

    protected function setupLocalPackageRepository(): void
    {
        $laravelPackageDir = base_path('../unload-laravel');

        $composer = json_decode(file_get_contents('./composer.json'), true);
        $composer['minimum-stability'] = 'dev';
        $composer['repositories'] = [
            [
                'type' => 'path',
                'url' => $laravelPackageDir,
            ]
        ];

        file_put_contents('./composer.json', json_encode($composer));
    }
}
