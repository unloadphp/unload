<?php

namespace App\Tasks;

use App\Aws\ContinuousIntegration;
use App\Configs\BootstrapConfig;
use App\Oidcs\OidcFactory;

class InitContinuousIntegrationTask
{
    private BootstrapConfig $config;

    public function __construct(BootstrapConfig $config)
    {
        $this->config = $config;
    }

    public function handle(): void
    {
        $ci = new ContinuousIntegration($this->config);
        $oidc = OidcFactory::fromBootstrap($this->config);
        $ci->createStack($oidc)->wait();
    }
}
