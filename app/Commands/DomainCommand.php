<?php

namespace App\Commands;

use App\Aws\Domain;

class DomainCommand extends Command
{
    protected $signature = 'domain {domain : The name of the domain. Example: example.com}';
    protected $description = 'Register an domain within the dns service';

    public function handle(Domain $dns)
    {
        $domain = $this->argument('domain');

        if (!$domain) {
            $this->error('Invalid domain specified. Example: example.com');
            return;
        }

        $this->info("Registering '$domain' in the dns service.");

        $nameservers = $dns->register($domain);

        $this->line("Domain '$domain' has been successfully registered.");
        $this->line('Use the following nameservers to configure within your domain register:');
        $this->table([], array_map(fn($nameserver) => (array) $nameserver, $nameservers));
    }
}
