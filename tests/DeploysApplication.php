<?php

namespace Tests;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;

trait DeploysApplication
{
    public function process(): PendingProcess
    {
        return Process::timeout(3600)->path(base_path('tests/Fixtures/tmp'))->env([
            'AWS_SHARED_CREDENTIALS_FILE' => getenv('AWS_SHARED_CREDENTIALS_FILE'),
        ]);
    }

    protected function setupTempDirectory(): void
    {
        echo 'Configure temporary directory'.PHP_EOL;

        $tempDir = base_path('tests/Fixtures/tmp');
        exec('rm -rf '.$tempDir);
        exec('mkdir -p '.$tempDir);
        chdir($tempDir);
    }

    protected function setupSampleApp(): void
    {
        echo 'Configure sample application'.PHP_EOL;

        $complexContent = file_get_contents(base_path('tests/Fixtures/unload.complex.yaml'));
        file_put_contents('unload.yaml', $complexContent);

        $sampleApp = base_path('tests/Fixtures/sample/*');
        exec("cp -rf $sampleApp .");
    }

    protected function setupLocalPackageRepository(): void
    {
        echo 'Configure unload-laravel package composer repository'.PHP_EOL;

        $composer = json_decode(file_get_contents('./composer.json'), true);
        $composer['minimum-stability'] = 'dev';
        $composer['repositories'] = [
            [
                'type' => 'git',
                'url' => 'https://github.com/unloadphp/unload-laravel.git',
            ]
        ];

        file_put_contents('./composer.json', json_encode($composer, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
    }
}
