<?php

namespace App\Tasks;

use App\Configs\UnloadConfig;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ExecuteSamDeleteTask
{
    public function handle(UnloadConfig $unload, OutputInterface $output): void
    {
        $delete = Process::fromShellCommandline(
            "sam delete --stack-name={$unload->appStackName()} --region={$unload->region()} --profile={$unload->profile()} --no-prompts",
            null,
            ['SAM_CLI_TELEMETRY' => 0]
        );

        $delete->setTimeout(3600);
        $delete->run(fn ($type, $line) => $output->write($line));

        if ($delete->getExitCode()) {
            throw new \Exception('Failed to delete stack: ', $delete->getOutput());
        }
    }
}
