<?php

namespace App\Aws;

use App\Configs\UnloadConfig;
use Aws\Ssm\SsmClient;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Collection;

class SystemManager
{
    private SsmClient $ssm;
    private UnloadConfig $unload;

    public function __construct(UnloadConfig $unload)
    {
        $this->ssm = new SsmClient(['profile' => $unload->profile(), 'region' => $unload->region(), 'endpoint' => $unload->endpoint(), 'version' => '2014-11-06']);
        $this->unload = $unload;
    }

    public function initialized(): bool
    {
        $environmentParts = $this->ssm->getParametersByPath(['Path' => $this->unload->ssmEnvPath(),]);
        return !!$environmentParts->search('Parameters');
    }

    public function flushEnvironment(): void
    {
        $environmentParts = $this->ssm->getParametersByPath(['Path' => $this->unload->ssmPath(), 'Recursive' => true]);

        foreach($environmentParts->search('Parameters') as $parameter) {
            $this->ssm->deleteParameter(['Name' => $parameter['Name'],]);
        }
    }

    public function fetchEnvironment(bool $decrypt = false): string
    {
        $environmentParts = $this->ssm->getParametersByPath(['Path' => $this->unload->ssmEnvPath(),]);
        $encryptedEnvironment = collect($environmentParts->search('Parameters'))->implode('Value');

        if ($decrypt) {
            $secret = $this->ssm->getParameter(['Name' => $this->unload->ssmCiPath('key'),])->search('Parameter.Value');
            $encrypter = new Encrypter(base64_decode($secret), 'aes-256-cbc');
            try {
                return $encrypter->decrypt($encryptedEnvironment);
            } catch (DecryptException) {
                return '';
            }
        }

        return $encryptedEnvironment;
    }

    public function putEnvironment(
        string $newEnvironment,
        string $existingEnvironment = null,
        bool $rotate = false
    ): void {
        if ($newEnvironment == $existingEnvironment && $rotate === false) {
            return;
        }

        $secret = random_bytes(32);
        if (!$rotate) {
            $secret = $this->ssm->getParameter(['Name' => $this->unload->ssmCiPath('key'),])->search('Parameter.Value');
            $secret = base64_decode($secret);
        }

        $encrypter = new Encrypter($secret, 'aes-256-cbc');

        $env = $encrypter->encrypt($newEnvironment);
        $parts = str_split($env, 4000);
        $existingParts = $this->retrieveParametersByPath($this->unload->ssmEnvPath());

        if ($existingParts->count() > count($parts)) {
            for ($key = count($parts); $key <= $existingParts->count(); $key++) {
                $this->ssm->deleteParameter(['Name' => $this->unload->ssmEnvPath('p'.$key)]);
            }
        }

        foreach($parts as $key => $part) {
            $this->putParameter($this->unload->ssmEnvPath('p'.++$key), $part);
        }

        if ($rotate) {
            $this->putCiParameter('key', base64_encode($secret));
        }
    }

    public function putCiParameter(string $name, string $value, $secure = false): void
    {
        $this->putParameter($this->unload->ssmCiPath($name), $value, $secure);
    }

    public function putParameter(string $name, string $value, $secure = false): void
    {
        $properties = [
            'Name' => $name,
            'Type' => $secure ? 'SecureString' : 'String',
            'Value' => $value,
            'Overwrite' => true,
        ];

        $this->ssm->putParameter($properties);

        $this->ssm->addTagsToResource([
            'ResourceType' => 'Parameter',
            'ResourceId' => $name,
            'Tags' => $this->unload->unloadTags(),
        ]);
    }

    public function retrieveParametersByPath(string $path): Collection
    {
        return collect($this->ssm->getParametersByPath(['Path' => $path])->search('Parameters'))->keyBy('Name');
    }
}
