<?php

namespace App\Tasks;

use App\Aws\Certificate;
use App\Cloudformation;

class InitCertificateTask
{
    public function handle(Certificate $certificate): void
    {
        $certificate->createStack()?->wait();
    }
}
