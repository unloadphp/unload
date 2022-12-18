<?php

namespace App\Tasks;

use App\Path;
use App\Configs\UnloadConfig;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class CleanupFilesTask
{
    use InteractsWithIO;

    public function handle(UnloadConfig $unloadConfig)
    {
        Collection::make([
            Path::tmpApp('.env'),
            Path::tmpApp('.env.example'),
            Path::tmpApp('.phpunit.result.cache'),
            Path::tmpApp('package-lock.json'),
            Path::tmpApp('phpunit.xml'),
            Path::tmpApp('readme.md'),
            Path::tmpApp('Readme.md'),
            Path::tmpApp('unload*'),
            Path::tmpApp('yarn.lock'),
            Path::tmpApp('webpack.mix.js'),
            Path::tmpApp('server.php'),
            Path::tmpApp('node_modules'),
            Path::tmpApp('README.md'),

            Path::tmpApp('resources/css'),
            Path::tmpApp('resources/js'),
            Path::tmpApp('resources/less'),
            Path::tmpApp('resources/sass'),
            Path::tmpApp('resources/scss'),
            Path::tmpApp('storage/cache'),
            Path::tmpApp('storage/debugbar'),
            Path::tmpApp('storage/logs'),
            Path::tmpApp('storage/sessions'),
            Path::tmpApp('storage/testing'),
            Path::tmpApp('storage/oauth-private.key'),
            Path::tmpApp('storage/oauth-public.key'),
        ])
            ->merge($unloadConfig->ignoreFiles())
            ->each(fn(string $path) => $this->removeGlob($path));
    }

    protected function removeGlob(string $path): void
    {
        foreach(File::glob($path) as $path) {
            if (File::isDirectory($path)) {
                File::deleteDirectory($path, false);
                return;
            }
            File::delete($path);
        }
    }
}
