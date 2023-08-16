<?php

namespace App\Tasks;

use App\Aws\Network;

class DestroyNetworkTask
{
    public function handle(Network $network): void
    {
        $network->deleteStack()?->wait();
    }
}
