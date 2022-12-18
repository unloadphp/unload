<?php

namespace App\Commands;

use App\Aws\Lambda;

class ExecCommand extends Command
{
    protected $signature = 'exec {arguments*}';
    protected $description = 'Execute a cli command in the remote environment';

    public function handle(Lambda $lambda): void
    {
        $command = implode(' ', $this->argument('arguments'));

        $this->newLine();
        $this->info("Running '{$command}' in {$this->unload->cliFunction()} function");

        $stream = $lambda->exec($command);
        $this->output->write($stream);
    }
}
