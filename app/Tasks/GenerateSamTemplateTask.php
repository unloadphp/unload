<?php

namespace App\Tasks;

use App\Templates\SamTemplate;

class GenerateSamTemplateTask
{
    public function handle(SamTemplate $samTemplate): void
    {
        $samTemplate->make();
    }
}
