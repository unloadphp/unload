<?php

namespace App\Configs;

use App\Path;
use Illuminate\Support\Facades\File;

class LayerConfig
{
    private UnloadConfig $unload;
    private array $layers;

    public function __construct(UnloadConfig $unload)
    {
        $layersPath = Path::layersFile();
        if (!File::exists(Path::layersFile())) {
            throw new \Exception("Failed to load bref layers from {$layersPath}. Make sure you have bref package installed in the project folder.");
        }

        $this->unload = $unload;
        $this->layers = json_decode(file_get_contents($layersPath), true);
    }

    public function php(): string
    {
        $version = $this->layers["php-{$this->version()}"][$this->unload->region()];
        return "arn:aws:lambda:{$this->unload->region()}:209497400698:layer:php-{$this->version()}:$version";
    }

    public function fpm(): string
    {
        $version = $this->layers["php-{$this->version()}-fpm"][$this->unload->region()];
        return "arn:aws:lambda:{$this->unload->region()}:209497400698:layer:php-{$this->version()}-fpm:$version";
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
}
