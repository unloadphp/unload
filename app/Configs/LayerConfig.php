<?php

namespace App\Configs;

use App\Path;

class LayerConfig
{
    private UnloadConfig $unload;
    private array $php;
    private array $extensions;

    const PRELOADED_BUT_DISABLED = ['intl', 'apcu', 'pdo_pgsql'];

    public function __construct(UnloadConfig $unload)
    {
        $this->unload = $unload;
        $this->php = [
            'account' => 534081306603,
            'layers' => json_decode(file_get_contents(Path::layersFile()), true),
        ];
        $this->extensions = [
            'account' => 534081306603,
            'layers' => json_decode(file_get_contents(Path::extensionFile()), true),
        ];
    }

    public function php(): string
    {
        $layer = "{$this->prefix()}php-{$this->version()}";

        if (empty($this->php['layers'][$layer])) {
            throw new \Exception("Layer $layer not supported. See https://runtimes.bref.sh.");
        }

        $version = $this->php['layers'][$layer][$this->unload->region()];
        return "arn:aws:lambda:{$this->unload->region()}:{$this->php['account']}:layer:$layer:$version";
    }

    public function fpm(): string
    {
        $layer = "{$this->prefix()}php-{$this->version()}-fpm";

        if (empty($this->php['layers'][$layer])) {
            throw new \Exception("Layer $layer not supported. See https://runtimes.bref.sh.");
        }

        $version = $this->php['layers'][$layer][$this->unload->region()];
        return "arn:aws:lambda:{$this->unload->region()}:{$this->php['account']}:layer:$layer:$version";
    }

    public function extensions(): array
    {
        $layers = [];

        foreach ($this->unload->extensions() as $extension) {
            if (in_array($extension, self::PRELOADED_BUT_DISABLED)) {
                continue;
            }

            $layer = "{$this->prefix()}{$extension}-php-{$this->version()}";

            if (empty($this->extensions['layers'][$layer])) {
                throw new \Exception("Extension $extension not supported. See https://github.com/brefphp/extra-php-extensions.");
            }

            $version = $this->extensions['layers'][$this->unload->region()];
            $layers[] = "arn:aws:lambda:{$this->unload->region()}:{$this->extensions['account']}:layer:$layer:$version";
        }

        return $layers;
    }

    public function console(): string
    {
        $version = $this->php['layers']["console"][$this->unload->region()];
        return "arn:aws:lambda:{$this->unload->region()}:{$this->php['account']}:layer:console:$version";
    }

    public function version(): string
    {
        return number_format($this->unload->php(), 1, '', ' ');
    }

    protected function prefix(): string
    {
        return $this->unload->architecture() == 'arm64' ? 'arm-' : '';
    }
}
