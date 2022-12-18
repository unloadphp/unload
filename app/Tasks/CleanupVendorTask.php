<?php

namespace App\Tasks;

use App\Path;
use App\Configs\UnloadConfig;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

class CleanupVendorTask
{
    public function handle(): void
    {
        // remove ignored files here
        $rules = [
            'README* CHANGELOG* FAQ* CONTRIBUTING* HISTORY* UPGRADING* UPGRADE* package.json package.lock demo example examples doc docs readme* changelog* composer*',
            '.travis.yml .scrutinizer.yml phpcs.xml* phpcs.php phpunit.xml* phpunit.php test tests Tests travis patchwork.json psalm*xml .psalm .php_cs .github phpstan.neon.dist .php_cs.dist *.neon .gitignore docker-compose.yml .dockerignore .editorconfig',
        ];

        foreach(File::directories(Path::tmpApp('vendor')) as $provider) {
            if ($provider == 'composer') {
                return;
            }

            foreach (File::directories($provider) as $package) {
                foreach ((array)$rules as $part) {
                    $patterns = explode(' ', trim($part));

                    foreach ($patterns as $pattern) {
                        foreach (glob($package . '/' . $pattern) as $file) {
                            File::isDirectory($file) ? File::deleteDirectory($file) : File::delete($file);
                        }
                    }
                }
            }
        }
    }
}
