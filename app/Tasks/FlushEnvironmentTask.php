<?php

namespace App\Tasks;

use App\Aws\SystemManager;

class FlushEnvironmentTask
{
    public function handle(SystemManager $parameterStore): void
    {
        $parameterStore->flushEnvironment();
    }
}
