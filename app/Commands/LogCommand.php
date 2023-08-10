<?php

namespace App\Commands;

use App\Configs\UnloadConfig;
use Illuminate\Support\Facades\Process;

class LogCommand extends Command
{
    protected $signature = 'log {--env=} {--tail} {--filter} {--start} {--end} {function}';
    protected $description = 'Query remote logs by function type';

    public function handle(UnloadConfig $config): void
    {
        $function = explode(':', $this->argument('function'));
        $functionType = $function[0] ?? '';
        $functionName = match($functionType) {
            'web' => 'WebFunction',
            'cli' => 'CliFunction',
            'deploy' => 'DeployFunction',
            'worker' => ucfirst($function[1]).'WorkerFunction',
            default => throw new \Exception("Specified '$functionType' function type isn't valid, please use one of web, cli, api, deploy or worker:*")
        };

        $command = sprintf(
            "sam logs --profile=%s --region=%s --stack-name=%s --name=%s",
            $config->profile(),
            $config->region(),
            $config->appStackName(),
            $functionName,
        );

        if ($this->option('start')) {
            $command .= ' --s='.$this->option('start');
        }

        if ($this->option('end')) {
            $command .= ' --e='.$this->option('start');
        }

        if ($this->option('filter')) {
            $command .= ' --filter='.$this->option('filter');
        }

        if ($this->option('tail')) {
            $command .= ' --tail ';
        }

        Process::env(['SAM_CLI_TELEMETRY' => 0])->run($command, function (string $type, string $output) {
            $this->output->write($output);
        });
    }
}
