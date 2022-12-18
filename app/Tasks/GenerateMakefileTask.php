<?php

namespace App\Tasks;

use App\Path;
use App\Configs\UnloadConfig;
use Illuminate\Support\Facades\File;

class GenerateMakefileTask
{
    public function handle(UnloadConfig $unload): void
    {
        File::put(
            Path::tmpAppMakefile(),
            /** @lang Makefile */
            <<<MAKEFILE
build-WebFunction:
	cp -R . $(ARTIFACTS_DIR);
	find $(ARTIFACTS_DIR) -type f -exec chmod 664 {} +
	find $(ARTIFACTS_DIR) -type d -exec chmod 755 {} +
	find $(ARTIFACTS_DIR) -type f -exec touch -t 201203101513 2>/dev/null {} +
	find $(ARTIFACTS_DIR) -type d -exec touch -t 201203101513 2>/dev/null {} +
MAKEFILE
        );
    }
}
