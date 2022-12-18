<?php

namespace App\Tasks;

use App\Path;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class EmptyAwsCredentialTask
{
    public function handle(): void
    {
        $configs = (new Finder())->in(Path::tmpApp('config'));

        foreach($configs as $config) {
            if ($config->isDir()) {
                continue;
            }

            $content = Str::of(File::get($config->getRealPath()))
                ->replace('AWS_ACCESS_KEY_ID', 'UNLOAD_NULL')
                ->replace('AWS_SECRET_ACCESS_KEY', 'UNLOAD_NULL')
                ->replace('AWS_SESSION_TOKEN', 'UNLOAD_NULL')
                ->toString();

            File::put($config->getRealPath(), $content);
        }
    }
}
