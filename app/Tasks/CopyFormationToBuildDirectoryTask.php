<?php

namespace App\Tasks;

use App\Configs\UnloadConfig;
use App\Path;
use Illuminate\Support\Facades\File;

class CopyFormationToBuildDirectoryTask
{
    public function handle(UnloadConfig $unload): void
    {
        // copy cloudformation templates, because phar path can't be read outside of php
        File::makeDirectory(Path::tmpCloudformation(), force: true);
        File::copyDirectory(base_path('cloudformation'), Path::tmpCloudformation());
    }
}
