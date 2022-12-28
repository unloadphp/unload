<?php

namespace App\Configs;

use App\Path;

class LayerConfig
{
    private UnloadConfig $unload;
    private array $layers;
    private array $extensions;

    const PRELOADED_BUT_DISABLED = ['intl', 'apcu', 'pdo_pgsql'];

    public function __construct(UnloadConfig $unload)
    {
        $this->unload = $unload;
        $this->layers = json_decode(file_get_contents(Path::layersFile()), true);
        $this->extensions = json_decode(file_get_contents(Path::extensionFile()), true);
    }

    public function php(): string
    {
        $layer = "{$this->prefix()}php-{$this->version()}";
        $version = $this->layers[$layer][$this->unload->region()];

        if (empty($this->layers[$layer])) {
            throw new \Exception("Layer $layer not supported. See https://runtimes.bref.sh.");
        }

        return "arn:aws:lambda:{$this->unload->region()}:209497400698:layer:$layer:$version";
    }

    public function fpm(): string
    {
        $layer = "{$this->prefix()}php-{$this->version()}-fpm";

        if (empty($this->layers[$layer])) {
            throw new \Exception("Layer $layer not supported. See https://runtimes.bref.sh.");
        }

        $version = $this->layers[$layer][$this->unload->region()];
        return "arn:aws:lambda:{$this->unload->region()}:209497400698:layer:$layer:$version";
    }

    public function extensions(): array
    {
        $layers = [];

        foreach ($this->unload->extensions() as $extension) {
            if (in_array($extension, self::PRELOADED_BUT_DISABLED)) {
                continue;
            }

            $layer = "{$this->prefix()}{$extension}-php-{$this->version()}";

            if (empty($this->extensions[$layer])) {
                throw new \Exception("Extension $extension not supported. See https://github.com/brefphp/extra-php-extensions.");
            }

            $version = $this->extensions[$layer][$this->unload->region()];
            $layers[] = "arn:aws:lambda:{$this->unload->region()}:403367587399:layer:$layer:$version";
        }

        return $layers;
    }

    public function console(): string
    {
        $version = $this->layers["console"][$this->unload->region()];
        return "arn:aws:lambda:{$this->unload->region()}:209497400698:layer:console:$version";
    }

    public function version(): string
    {
        return number_format($this->unload->php(), 1, '', ' ');
    }

    protected function prefix(): string
    {
        return $this->unload->runtime() == 'provided.arm' ? 'arm-' : '';
    }
}
