<?php

namespace App\Constructs;

use App\Aws\Domain;
use App\Cloudformation;
use App\Path;
use Illuminate\Support\Facades\App;
use Symfony\Component\Yaml\Tag\TaggedValue;

trait DnsConstruct
{
    protected function setupDns(): self
    {
        if (!$this->unloadConfig->domains()) {
            return $this;
        }

        /** @var Domain $domain */
        $domain = App::make(Domain::class);
        $domains = $domain->list();

        return $this->append('Resources', [
            'DNSStack' => [
                'Type' => 'AWS::Serverless::Application',
                'Properties' => [
                    'Tags' => $this->unloadConfig->unloadTagsPlain(),
                    'Location' => Cloudformation::compile("construct/dns.yaml", compact('domains')),
                    'Parameters' => [
                        'DistributionDomain' => new TaggedValue('GetAtt', 'CloudfrontStack.Outputs.URL'),
                    ],
                ],
            ]
        ]);
    }
}
