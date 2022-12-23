<?php

namespace App\Tasks;

use App\Aws\ContinuousIntegration;
use App\Aws\Network;
use App\Configs\BootstrapConfig;

class InitNetworkTask
{
    private BootstrapConfig $config;

    public function __construct(BootstrapConfig $config)
    {
        $this->config = $config;
    }

    public function handle(): void
    {
        $ci = new ContinuousIntegration($this->config);
        $network = new Network($this->config, $ci);
        $network->createStack(
            $this->config->vpc(),
            $this->config->nat(),
            $this->config->vpc(),
        )->wait();
    }
}
