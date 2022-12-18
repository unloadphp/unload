<?php

namespace App\Oidcs;

class Github implements OidcInterface
{
    private string $organization;
    private string $repository;
    private string $branch;

    public function __construct(string $organization, string $repository, string $branch)
    {
        $this->organization = $organization;
        $this->repository = $repository;
        $this->branch = $branch;
    }

    public function thumbprint(): string
    {
        return '6938fd4d98bab03faadb97b34396831e3780aea1';
    }

    public function url(): string
    {
        return 'https://token.actions.githubusercontent.com';
    }

    public function audience(): string
    {
        return 'sts.amazonaws.com';
    }

    public function claim(): string
    {
        return "repo:$this->organization/$this->repository:ref:refs/heads/$this->branch";
    }
}
