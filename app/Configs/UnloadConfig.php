<?php

namespace App\Configs;

use App\Path;
use Aws\Sts\StsClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

class UnloadConfig
{
    protected string $accountId = '';
    protected array $config = [];
    protected array $ignoreFiles = [];
    protected ?InputInterface $input;

    public function __construct(
        array $config = [],
        array $ignoreFiles = [],
        InputInterface $input = null
    ) {
        $this->config = $config;
        $this->ignoreFiles = $ignoreFiles;
        $this->input = $input;
    }

    public static function fromCommand(InputInterface $input = null): self
    {
        $unloadConfigPath = Path::unloadTemplatePath($input?->getOption('config'));
        $config = [];
        if (file_exists($unloadConfigPath)) {
            $config = Yaml::parse(file_get_contents($unloadConfigPath));

            $validator = new \Opis\JsonSchema\Validator();
            $validator->resolver()->registerFile('https://unload.dev/unload01.json', resource_path('unload01.json'));
            $validated = $validator->validate(Helper::toJSON($config), 'https://unload.dev/unload01.json');

            if (!$validated->isValid()) {
                $validationMessages = implode("\n  ", (new ErrorFormatter())->formatFlat($validated->error()));
                throw new \Exception($validationMessages);
            }
        }

        $ignoreFiles = [];
        if (file_exists(Path::ignoreFile())) {
            $ignoreFiles = explode(PHP_EOL, file_get_contents(Path::ignoreFile()));
        }

        return new self(
            $config,
            $ignoreFiles,
            $input,
        );
    }

    public function app(): string
    {
        return (string) Arr::get($this->config, 'app');
    }

    public function env(): string
    {
        return Arr::get($this->config, 'env', 'develop');
    }

    public function resourcePrefix(): string
    {
        return "unload-{$this->env()}-{$this->app()}";
    }

    public function ssmPath(string $parameter = null): string
    {
        return rtrim("/{$this->app()}/{$this->env()}/$parameter", '/');
    }

    public function ssmEnvPath(string $parameter = null): string
    {
        return $this->ssmPath("env/$parameter");
    }

    public function ssmCiPath(string $parameter = null): string
    {
        return $this->ssmPath("ci/$parameter");
    }

    public function region(): string
    {
        return Arr::get($this->config, 'region', 'us-east-1');
    }

    public function profile(): ?string
    {
        if (!file_exists(getenv('HOME').'/.aws/credentials')) {
            return null;
        }

        return Arr::get($this->config, 'profile', 'default');
    }

    public function runtime(): string
    {
        $runtime = Arr::get($this->config, 'runtime', 'provided');
        if ($runtime == 'provided') {
            return 'provided.al2';
        }
        return 'docker';
    }

    public function memory(): int
    {
        return (int) Arr::get($this->config, 'memory', 1024);
    }

    public function timeout(): int
    {
        return (int) Arr::get($this->config, 'timeout', 300);
    }

    public function tmp(): int
    {
        return (int) Arr::get($this->config, 'tmp', 512);
    }

    public function concurrency(): ?int
    {
        return (int) Arr::get($this->config, 'concurrency');
    }

    public function provision(): int
    {
        return (int) Arr::get($this->config, 'provision');
    }

    public function php(): string
    {
        if(isset($this->config['php'])) {
            return (string) $this->config['php'];
        }
        return '8.1';
    }

    public function warm(): int|array
    {
        return Arr::get($this->config, 'warm', 0);
    }

    public function defaultWarm(): int
    {
        $warm = $this->warm();
        $default = is_array($warm) ? max(Arr::get($warm, 'day'), Arr::get($warm, 'night')) : $warm;
        return (int) $default;
    }


    public function network(): string|false
    {
        return Arr::get($this->config, 'network');
    }

    public function appStackName(): string
    {
        return "unload-{$this->env()}-{$this->app()}-app";
    }

    public function ciStackName(): string
    {
        return "unload-{$this->env()}-{$this->app()}-ci";
    }

    public function networkStackName(): string
    {
        return "unload-{$this->env()}-network";
    }

    public function certificateStackName(): string
    {
        return "unload-{$this->env()}-{$this->app()}-acm";
    }

    public function buckets(): array
    {
        return (array) Arr::get($this->config, 'buckets', []);
    }

    public function queues(): array
    {
        return (array) Arr::get($this->config, 'queues', []);
    }

    public function extensions(): array
    {
        return (array) Arr::get($this->config, 'extensions', []);
    }

    public function domains(): array
    {
        $domains = (array) Arr::get($this->config, 'domains', []);
        sort($domains);
        return $domains;
    }

    public function firewallGeoLocations(): array
    {
        if (Arr::has($this->config, 'firewall.geo-blacklist')) {
            return (array) Arr::get($this->config, 'firewall.geo-blacklist', []);
        } elseif (Arr::has($this->config, 'firewall.geo-whitelist')) {
            return (array) Arr::get($this->config, 'firewall.geo-whitelist', []);
        } else {
            return [];
        }
    }

    public function firewallGeoType(): string
    {
        if (Arr::has($this->config, 'firewall.geo-blacklist')) {
            return 'blacklist';
        } elseif (Arr::has($this->config, 'firewall.geo-whitelist')) {
            return 'whitelist';
        } else {
            return 'none';
        }
    }

    public function firewallBurstLimit()
    {
        return Arr::get($this->config, 'firewall.burst-limit', 5000);
    }

    public function firewallRateLimit()
    {
        return Arr::get($this->config, 'firewall.rate-limit', 10000);
    }

    public function database(): array
    {
        return (array) Arr::get($this->config, 'database', []);
    }

    public function cache(): array
    {
        return (array) Arr::get($this->config, 'cache', []);
    }

    public function nat(): string|false
    {
        return Arr::get($this->config, 'nat', false);
    }

    public function build(): array
    {
        return array_filter(explode(PHP_EOL, Arr::get($this->config, 'build', '')));
    }

    public function deploy(): string
    {
        return Str::replace('php artisan ', '', (string) Arr::get($this->config, 'deploy', ''));
    }

    public function tmpBuildAssetHash(): string
    {
        return file_get_contents(Path::tmpAssetHash());
    }

    public function cliFunction(): string
    {
        return "{$this->resourcePrefix()}-cli";
    }

    public function deployFunction(): string
    {
        return "CodeDeployHook_{$this->resourcePrefix()}";
    }

    public function cliFunctionMemory(): int
    {
        return (int) Arr::get($this->config, 'cli.memory', $this->memory());
    }

    public function cliFunctionTimeout(): int
    {
        return (int) Arr::get($this->config, 'cli.timeout', 900);
    }

    public function cliFunctionTmp(): int
    {
        return (int) Arr::get($this->config, 'cli.tmp', $this->tmp());
    }

    public function cliFunctionConcurrency(): int
    {
        return (int) Arr::get($this->config, 'cli.concurrency', $this->concurrency());
    }

    public function cliFunctionProvision(): int
    {
        return (int) Arr::get($this->config, 'cli.provision', $this->provision());
    }

    public function webFunction(): string
    {
        return "{$this->resourcePrefix()}-web";
    }

    public function webFunctionMemory(): int
    {
        return (int) Arr::get($this->config, 'web.memory', $this->memory());
    }

    public function webFunctionTimeout(): int
    {
        return (int) Arr::get($this->config, 'web.timeout', 30);
    }

    public function webFunctionConcurrency(): int
    {
        return (int) Arr::get($this->config, 'web.concurrency', $this->concurrency());
    }

    public function webFunctionProvision(): int
    {
        return (int) Arr::get($this->config, 'web.provision', $this->provision());
    }

    public function webFunctionTmp(): int
    {
        return (int) Arr::get($this->config, 'web.tmp', $this->tmp());
    }

    public function workerFunction(string $queue): string
    {
        $queue = strtolower($queue);
        return "{$this->resourcePrefix()}-worker-$queue";
    }

    public function databaseName(): string
    {
        return Str::replace(['-', '_'], '', $this->app());
    }

    public function databaseUsername(): string
    {
        return $this->databaseName().'_user';
    }

    public function accountId()
    {
        if (!$this->accountId) {
            $sts = new StsClient(['region' => $this->region(), 'profile' => $this->profile(), 'version' => '2011-06-15']);
            $this->accountId = $sts->getCallerIdentity()->search('Account');
        }
        return $this->accountId;
    }

    public function signing(): bool
    {
        return Arr::get($this->config, 'signing') === 'yes';
    }

    public function assetsBucket(): string
    {
        return "{$this->resourcePrefix()}-assets-{$this->accountId()}";
    }

    public function ignoreFiles(): array
    {
        return (array) $this->ignoreFiles;
    }

    public function template(bool $relative = false): string
    {
        return Path::unloadTemplatePath($this->env(), $relative);
    }

    public function unloadTags(): array
    {
        return [
            [
                'Key' => 'unload',
                'Value' => 'unload',
            ],
            [
                'Key' => 'unload:app',
                'Value' => $this->app(),
            ],
            [
                'Key' => 'unload:env',
                'Value' => $this->env(),
            ],
            [
                'Key' => 'unload:version',
                'Value' => App::version(),
            ],
        ];
    }

    public function unloadGlobalTags(): array
    {
        return [
            [
                'Key' => 'unload',
                'Value' => 'unload',
            ],
            [
                'Key' => 'unload:version',
                'Value' => App::version(),
            ],
        ];
    }

    public function unloadTagsPlain(): array
    {
        return [
            'unload' => 'unload',
            'unload:app' => $this->app(),
            'unload:env' => $this->env(),
            'unload:version' => App::version(),
        ];
    }

    public function toArray(): array
    {
        return $this->config;
    }
}
