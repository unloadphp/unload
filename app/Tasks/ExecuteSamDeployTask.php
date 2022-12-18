<?php

namespace App\Tasks;

use App\Configs\UnloadConfig;
use App\Path;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ExecuteSamDeployTask
{
    public function handle(UnloadConfig $unload, OutputInterface $output): void
    {
        $deploy = Process::fromShellCommandline(
            sprintf(
                "sam deploy --template=%s  --config-file=%s",
                Path::tmpBuildTemplate(),
                Path::tmpSamConfig()
            ),
            null,
            ['SAM_CLI_TELEMETRY' => 0]
        );

        $deploy->setTimeout(3600);
        $deploy->run(fn ($type, $line) => $output->write($line));

        if ($deploy->getExitCode()) {
            throw new \Exception('AWS sam failed to deploy the stack. Please check cloudformation output for exact reason.');
        }
    }
}
