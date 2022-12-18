<?php

namespace App\Tasks;

use App\Templates\SamConfigTemplate;

class GenerateSamConfigTask
{
    public function handle(SamConfigTemplate $samConfigTemplate): void
    {
        $samConfigTemplate->make();
    }
}
