<?php

namespace App\Commands;


use App\System;
use App\Aws\SystemManager;

class EnvCommand extends Command
{
    protected $signature = 'env {--rotate}';
    protected $description = 'Update application environment configuration';

    public function handle(SystemManager $manager): void
    {
        $this->info('Retrieving environment configuration from system manager');

        $environment = $manager->fetchEnvironment(decrypt: true);
        $newEnvironment = System::open($environment);

        $this->step(
            'Saving environment configuration to system manager',
            fn() => $manager->putEnvironment($newEnvironment, $environment, (bool) $this->option('rotate'))
        );

        $this->newLine();
        $this->line("  SSM Path: {$this->unload->ssmPath()}");
    }
}
