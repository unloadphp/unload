<?php

namespace App\Tasks;

use App\Path;
use App\Configs\UnloadConfig;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class ExtractStaticAssetsTask
{
    public function handle(UnloadConfig $unload): void
    {
        File::makeDirectory(Path::tmpAssetDirectory(), force: true);

        $this->extractAssetsFromPublicFolder();
        $this->injectErrorPages();
    }

    protected function extractAssetsFromPublicFolder(): void
    {
        $assets = (new Finder())
            ->in(Path::tmpApp('public'))
            ->notName('*.php')
            ->notName('.htaccess')
            ->ignoreVcs(true)
            ->ignoreDotFiles(false);

        foreach($assets as $asset) {
            if ($asset->isLink()) {
                continue;
            }

            if ($asset->isDir()) {
                File::copyDirectory($asset->getRealPath(), Path::tmpAsset($asset->getRelativePathname()));
            } else {
                File::copy($asset->getRealPath(), Path::tmpAsset($asset->getRelativePathname()));
            }
        }
    }

    protected function injectErrorPages(): void
    {
        if(! File::exists(Path::tmpAsset('503.html'))) {
            File::copy(resource_path('503.html'), Path::tmpAsset('503.html'));
        }
    }
}
