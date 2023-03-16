<?php

namespace App\Aws;

use App\Configs\UnloadConfig;
use Aws\Route53\Route53Client;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

class Domain
{
    private Route53Client $route53;
    private UnloadConfig $unload;

    public function __construct(UnloadConfig $unload)
    {
        $this->route53 = new Route53Client(['profile' => $unload->profile(), 'region' => $unload->region(), 'endpoint' => $unload->endpoint(), 'version' => 'latest',]);
        $this->unload = $unload;
    }

    public function list($onlyRoot = false, $wildcard = false): Collection
    {
        $zones = [];
        $domains = $this->unload->domains();
        $wildcards = [];
        $registeredZones = $this->route53->listHostedZones();

        foreach($domains as $domain) {
            $host = parse_url((strpos($domain, '://') === FALSE ? 'http://' : '') . trim($domain), PHP_URL_HOST);

            if (!preg_match('/[a-z0-9][a-z0-9\-]{0,63}\.[a-z]{2,6}(\.[a-z]{1,2})?$/i', $host, $match)) {
                throw new \Exception("Failed to parse domain zone. Check that '$domain' is a valid domain name");
            }

            $zone = $match[0];
            $registeredZone = $registeredZones->search("HostedZones[?Name=='{$zone}.']");
            if (!$registeredZone) {
                continue;
            }

            $zoneId = str_replace('/hostedzone/', '', $registeredZone[0]['Id']);
            if ($wildcard && $zone != $domain) {
                $wildcards["*.$zone"] = $zoneId;
            } else {
                $zones[$onlyRoot ? $zone : $domain] = $zoneId;
            }
        }

        return collect($zones)->merge($wildcards);
    }

    public function listRoot(): Collection
    {
        return $this->list(true, true);
    }

    public function register(string $domain): array
    {
        $zones = $this->route53->listHostedZones()->search("HostedZones[?Name=='{$domain}.']");

        if ($zones) {
            throw new \BadMethodCallException("Domain '$domain' already exists.");
        }

        $zone = $this->route53->createHostedZone([
            'CallerReference' => Uuid::uuid4()->toString(),
            'Name' => $domain,
        ]);

        $this->route53->changeTagsForResource([
            'ResourceType' => 'hostedzone',
            'ResourceId' => str_replace('/hostedzone/', '', $zone->search('HostedZone.Id')),
            'AddTags' => $this->unload->unloadTags(),
        ]);

        return $zone->search('DelegationSet.NameServers');
    }

    public function deregister(string $domain): array
    {
        $zones = $this->route53->listHostedZones()->search("HostedZones[?Name=='{$domain}.']");

        if (!$zones) {
            throw new \BadMethodCallException("Domain '$domain' doesn't exists.");
        }

        $this->route53->deleteHostedZone([
            'CallerReference' => Uuid::uuid4()->toString(),
            'Name' => $domain,
        ]);
    }
}
