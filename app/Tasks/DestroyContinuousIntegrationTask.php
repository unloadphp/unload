<?php

namespace App\Tasks;

use App\Aws\ContinuousIntegration;

class DestroyContinuousIntegrationTask
{
    public function handle(ContinuousIntegration $ci): void
    {
        $ci->deleteStack()?->wait();
    }
}
