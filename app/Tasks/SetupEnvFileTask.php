<?php

namespace App\Tasks;

use App\Aws\SystemManager;
use App\Path;
use Illuminate\Support\Facades\File;

class SetupEnvFileTask
{
    public function handle(SystemManager $parameterStore): void
    {
        File::put(Path::tmpAppEnv(), $parameterStore->fetchEnvironment());
    }
}
