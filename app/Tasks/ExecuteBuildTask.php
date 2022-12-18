<?php

namespace App\Tasks;

use App\Configs\UnloadConfig;
use App\Path;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ExecuteBuildTask
{
    public function handle(UnloadConfig $config): void
    {
        $commands = $config->build();
        if (!$commands) {
            $commands = $this->defaultBuildCommands();
        }
        $commands[] = 'php artisan vendor:publish --tag=unload --force';

        foreach($commands as $command) {
            $run = Process::fromShellCommandline($command, Path::tmpApp());

            $run->run(function ($type, $line) {
                echo $line;
            });

            if ($run->getExitCode() && $runError = $run->getErrorOutput()) {
                throw new \BadMethodCallException($runError);
            }
        }
    }

    protected function defaultBuildCommands(): array
    {
        $commands = [
            'composer install --ignore-platform-reqs --no-dev --prefer-dist --no-interaction --no-progress --ignore-platform-reqs --optimize-autoloader --classmap-authoritative',
            'php artisan route:cache',
        ];

        if (File::exists(Path::tmpApp('package.json'))) {
            $commands[] = 'npm install';
            $commands[] = 'npm run prod';
        }

        return $commands;
    }
}
