<?php

namespace Tests\Commands;

use Aws\Route53\Route53Client;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Exception\RuntimeException;
use Tests\TestCase;

class DomainCommandTest extends TestCase
{
    public function test_domain_fails_when_domain_isnt_set()
    {
        $this->expectException(RuntimeException::class);
        $this->artisan('domain')->execute();
    }

    public function test_domain_fails_when_domain_is_empty()
    {
        $this->artisan('domain', ['domain' => ''])
            ->expectsOutputToContain('Invalid domain specified. Example: example.com')
            ->execute();
    }

    public function test_new_domain_can_be_successfully_registered()
    {
        $domain = Uuid::uuid4()->toString().'.com';
        $route53 = new Route53Client(['endpoint' => 'http://localhost:4566', 'region' => 'us-east-1', 'version' => 'latest']);

        $this->artisan('domain', ['domain' => $domain])->assertSuccessful()->execute();

        $route53Domains = $route53->listHostedZonesByName(['Name' => $domain])->search('HostedZones[].Name');
        $this->assertContains("$domain.", $route53Domains);
    }

    public function test_duplicate_domain_fails_with_error()
    {
        $domain = Uuid::uuid4()->toString().'.com';

        $this->expectExceptionMessage("Domain '$domain' already exists.");
        $this->artisan('domain', ['domain' => $domain])->assertSuccessful()->execute();
        $this->artisan('domain', ['domain' => $domain])->execute();
    }
}
