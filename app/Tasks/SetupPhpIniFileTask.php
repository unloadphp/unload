<?php

namespace App\Tasks;

use App\Aws\SystemManager;
use App\Path;
use Illuminate\Support\Facades\File;

class SetupPhpIniFileTask
{
    public function handle(SystemManager $parameterStore): void
    {
        $phpIniPath = Path::tmpApp('php/conf.d/php.ini');

        if (!File::exists($phpIniPath)) {
            return;
        }

        /** Extend default bref php ini */
        File::ensureDirectoryExists(Path::tmpApp('php/conf.d'));
        File::put($phpIniPath, <<<PHPINI
opcache.validate_timestamps=0
opcache.enable_cli=1
expose_php=off
PHPINI
);
    }
}
