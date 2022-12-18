<?php

namespace App\Tasks;

use App\Path;
use Illuminate\Support\Facades\File;

class PrepareBuildDirectoryTask
{
    public function handle(): void
    {
        // prepare temp build direcotry
        File::deleteDirectory(Path::tmpDirectory());
        File::makeDirectory(Path::tmpDirectory(), force: true);
        File::deleteDirectory(Path::tmpAppDirectory());
        File::makeDirectory(Path::tmpAppDirectory());
    }
}
