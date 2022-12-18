<?php

namespace App;

class Path
{
    public static function current(): string
    {
        return getcwd();
    }

    public static function tmpDirectory(): string
    {
        return self::current().'/.unload';
    }

    public static function tmpSamDirectory()
    {
        return self::current()."/.aws-sam";
    }

    public static function tmpAppDirectory(): string
    {
        return self::tmpDirectory().'/app';
    }

    public static function tmpApp($file = ""): string
    {
        return self::tmpDirectory()."/app/$file";
    }

    public static function tmpAppEnv(): string
    {
        return self::tmpApp('.env.unload');
    }

    public static function tmpAppMakefile(): string
    {
        return self::tmpApp('Makefile');
    }

    public static function cloudformation($path = ''): string
    {
        return base_path("cloudformation/$path");
    }

    public static function tmpCloudformation($path = ''): string
    {
        return self::tmpDirectory()."/cloudformation/$path";
    }

    public static function tmpTemplate(): string
    {
        return self::tmpDirectory()."/template.yml";
    }

    public static function tmpSamConfig(): string
    {
        return self::tmpDirectory()."/samconfig.toml";
    }

    public static function tmpSamPipelineConfig(): string
    {
        return self::tmpSamDirectory()."/pipeline/pipelineconfig.toml";
    }

    public static function tmpAssetDirectory(): string
    {
        return self::tmpDirectory()."/asset";
    }

    public static function tmpAsset(string $path): string
    {
        return self::tmpAssetDirectory()."/$path";
    }

    public static function tmpAssetHash(): string
    {
        return self::tmpDirectory().'/asset_hash';
    }

    public static function tmpBuildDirectory(): string
    {
        return self::tmpDirectory()."/build";
    }

    public static function tmpBuild(string $path): string
    {
        return self::tmpBuildDirectory()."/$path";
    }

    public static function tmpBuildTemplate(): string
    {
        return self::tmpBuild("template.yaml");
    }

    public static function ignoreFile(): string
    {
        return self::current().'/.unloadignore';
    }

    public static function layersFile(): string
    {
        return self::tmpApp('/vendor/bref/bref/layers.json');
    }

    public static function unloadTemplatePath(?string $config, bool $relative = false): string
    {
        if (str_contains($config, '.yaml')) {
            if ($relative) {
                return $config;
            }
            return getcwd()."/".$config;
        }

        $prefix = "";
        if ($config && $config != 'production') {
            $prefix = ".$config";
        }

        if ($relative) {
            return "unload$prefix.yaml";
        }

        return getcwd()."/unload$prefix.yaml";
    }
}
