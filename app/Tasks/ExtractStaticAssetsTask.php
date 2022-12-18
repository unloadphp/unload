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
        // exteract static assets from build
        File::makeDirectory(Path::tmpAssetDirectory(), force: true);

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
}
