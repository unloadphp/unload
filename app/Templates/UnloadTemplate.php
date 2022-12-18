<?php

namespace App\Templates;

use Illuminate\Support\Facades\File;

class UnloadTemplate extends Template
{
    public function make(): bool
    {
        $template = str(File::get(resource_path('unload.yaml.stub')));
        foreach($this->unloadConfig->toArray() as $key => $value) {
            $template = $template->replace("%$key%", $value);
        }

        File::put($this->unloadConfig->template(), $template->toString());

        return true;
    }
}
