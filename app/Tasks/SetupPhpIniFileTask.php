<?php

namespace App\Tasks;

use App\Aws\SystemManager;
use App\Configs\LayerConfig;
use App\Configs\UnloadConfig;
use App\Path;
use Illuminate\Support\Facades\File;

class SetupPhpIniFileTask
{
    public function handle(UnloadConfig $unload): void
    {
        $phpIniPath = Path::tmpApp('php/conf.d/php.ini');

        $extensions = '';
        foreach($unload->extensions() as $extension) {
            if (in_array($extension, LayerConfig::PRELOADED_BUT_DISABLED)) {
                $extensions .= "extension=$extension\n";
            }
        }

        /**
         * Extend default bref php ini
         * @see https://github.com/brefphp/bref/blob/master/runtime/layers/fpm/php.ini
         */
        File::ensureDirectoryExists(Path::tmpApp('php/conf.d'));
        File::prepend($phpIniPath, <<<PHPINI
opcache.validate_timestamps=0
opcache.enable_cli=1
expose_php=off
opcache.file_cache="/tmp"
opcache.enable_file_override=1
opcache.file_cache_consistency_checks=0

$extensions

PHPINI
);
    }
}
