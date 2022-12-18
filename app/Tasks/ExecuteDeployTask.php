<?php

namespace App\Tasks;

use App\Configs\UnloadConfig;
use Illuminate\Support\Facades\Artisan;

class ExecuteDeployTask
{
    public function handle(UnloadConfig $config): void
    {
        $commands = $config->deploy();

        foreach($commands as $command) {
            Artisan::call('exec', $command);
        }
    }
}
