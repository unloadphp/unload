<?php

namespace App\Tasks;

use App\Aws\SystemManager;
use App\Configs\BootstrapConfig;
use Illuminate\Support\Str;

class InitEnvironmentTask
{
    private BootstrapConfig $config;

    public function __construct(BootstrapConfig $config)
    {
        $this->config = $config;
    }

    public function handle(): void
    {
        $parameterStore = new SystemManager($this->config);
        if ($parameterStore->initialized()) {
            return;
        }

        $appKey = 'base64:'.base64_encode(random_bytes(32));
        $password = Str::random(41);
        $initEnvironment = <<<ENV
APP_NAME={$this->config->app()}
APP_KEY={$appKey}

APP_DEBUG=false
APP_ENV={$this->config->env()}
SESSION_DRIVER=cookie
CACHE_DRIVER=dynamodb
CACHE_STORE=dynamodb

DB_CONNECTION=mysql
DB_PORT=3306

DB_DATABASE={$this->config->databaseName()}
DB_USERNAME={$this->config->databaseUsername()}
DB_PASSWORD={$password}

ENV;

        $parameterStore->putEnvironment($initEnvironment, rotate: true);
        $parameterStore->putCiParameter('database', $password, secure: true);
    }
}
