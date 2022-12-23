<?php

namespace App\Configs;

use Illuminate\Support\Arr;

class BootstrapConfig extends UnloadConfig
{
    public function provider(): string
    {
        return Arr::get($this->config, 'provider');
    }

    public function repository(): string
    {
        return Arr::get($this->config, 'repository');
    }

    public function repositoryOrganization(): string
    {
        $data = explode('/', (string) Arr::get($this->config, 'repository'));
        return $data[0] ?? '';
    }

    public function repositoryName(): string
    {
        $data = explode('/', (string) Arr::get($this->config, 'repository'));
        return $data[1] ?? '';
    }

    public function repositoryUuid(): string
    {
        return  (string) Arr::get($this->config, 'repositoryUuid');
    }

    public function audience(): string
    {
        return (string) Arr::get($this->config, 'audience');
    }

    public function branch(): string
    {
        return Arr::get($this->config, 'branch');
    }

    public function vpc(): string
    {
        return Arr::get($this->config, 'vpc');
    }

    public function nat(): string
    {
        return Arr::get($this->config, 'nat');
    }

    public function ssh(): bool
    {
        return (bool) Arr::get($this->config, 'ssh', true);
    }
}
