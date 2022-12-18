<?php

namespace App\Oidcs;

use App\Configs\BootstrapConfig;

class OidcFactory
{
    public static function fromBootstrap(BootstrapConfig $config): OidcInterface
    {
        switch ($config->provider()) {
            case 'github': {
                return new Github($config->repositoryOrganization(), $config->repositoryName(), $config->branch());
            }
            case 'bitbucket': {
                return new Bitbucket($config->repositoryOrganization(), $config->audience(), $config->repositoryUuid());
            }
        }

        throw new \BadMethodCallException("Invalid CI provider: {$config->provider()}");
    }
}
