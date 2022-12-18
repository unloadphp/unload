<?php

namespace App\Tasks;

use App\Path;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class CopySourceToBuildDirectoryTask
{
    public function handle(): void
    {
        $sources = (new Finder())
            ->in(Path::current())
            ->exclude('.idea')
            ->exclude('.unload')
            ->exclude('.aws-sam')
            ->exclude('.github')
            ->exclude('unload_test')
            ->exclude('vendor')
            ->exclude('node_modules')
            ->notName('rr')
            ->notName('node_modules')
            ->notPath('/^'.preg_quote('tests', '/').'/')
            ->ignoreVcs(true)
            ->ignoreDotFiles(true);

        foreach($sources as $source) {
            if ($source->isLink()) {
                continue;
            }

            if ($source->isDir()) {
                File::copyDirectory($source->getRealPath(), Path::tmpApp($source->getRelativePathname()));
            } else {
                File::copy($source->getRealPath(), Path::tmpApp($source->getRelativePathname()));
            }
        }
    }
}
