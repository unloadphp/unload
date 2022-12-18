<?php

namespace App\Tasks;

use App\Path;
use Illuminate\Support\Facades\File;

class CleanupPipelineConfigTask
{
    public function handle(): void
    {
        if (File::exists(Path::tmpSamDirectory())) {
            File::deleteDirectory(Path::tmpSamDirectory());
        }
    }
}
