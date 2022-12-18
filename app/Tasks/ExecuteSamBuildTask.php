<?php

namespace App\Tasks;

use App\Path;
use Symfony\Component\Process\Process;

class ExecuteSamBuildTask
{
    public function handle(): void
    {
        $build = Process::fromShellCommandline(
            sprintf("sam build --template=%s --base-dir=%s --build-dir=%s", Path::tmpTemplate(), Path::tmpAppDirectory(), Path::tmpBuildDirectory()),
            null,
            ['SAM_CLI_TELEMETRY' => 0]
        );

        $build->run();

        if ($build->getExitCode() && $buildError = $build->getErrorOutput()) {
            throw new \BadMethodCallException($buildError);
        }
    }
}
