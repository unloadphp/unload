<?php

namespace App\Tasks;

use App\Path;
use Illuminate\Support\Facades\File;

class DestroyComposerPlatformCheckTask
{
    public function handle(): void
    {
        $platformCheckFile = Path::tmpApp('vendor/composer/platform_check.php');

        if (File::exists($platformCheckFile)) {
            File::replace($platformCheckFile, '<?php '.PHP_EOL);
        }
    }
}
