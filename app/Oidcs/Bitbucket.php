<?php

namespace App\Oidcs;

class Bitbucket implements OidcInterface
{
    private string $audience;
    private string $repositoryUuid;

    public function __construct(string $organization, string $audience, string $repositoryUuid)
    {
        $this->organization = $organization;
        $this->audience = $audience;
        $this->repositoryUuid = $repositoryUuid;
    }

    public function thumbprint(): string
    {
        return 'a031c46782e6e6c662c2c87c76da9aa62ccabd8e';
    }

    public function url(): string
    {
        return "https://api.bitbucket.org/2.0/workspaces/$this->organization/pipelines-config/identity/oidc";
    }

    public function audience(): string
    {
        return $this->audience;
    }

    public function claim(): string
    {
        return "$this->repositoryUuid:*";
    }
}
