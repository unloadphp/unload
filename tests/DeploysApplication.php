<?php

namespace Tests;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
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

        file_put_contents('./composer.json', json_encode($composer, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
    }
}
