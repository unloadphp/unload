<?php

namespace App;

use Illuminate\Support\Facades\File;

class Cloudformation
{
    public static function get(string $template, array $data = []): string
    {
        $renderable = Path::tmpCloudformation("$template.php");

        if (File::exists($renderable)) {
            return self::compileCloudformationTemplate($renderable, $data);
        }

        return File::get(Path::tmpCloudformation($template));
    }

    public static function compile(string $template, array $data = []): string
    {
        $renderable = Path::tmpCloudformation("$template.php");
        $path = Path::tmpCloudformation($template);

        if (File::exists($renderable)) {
            $renderedTemplate = self::compileCloudformationTemplate($renderable, $data);
            File::makeDirectory(File::dirname($path), recursive: true, force: true);
            File::put($path, $renderedTemplate);
            return $path;
        }

        return Path::tmpCloudformation($template);
    }

    protected static function compileCloudformationTemplate(string $path, array $data): string
    {
        extract($data);
        ob_start();
        include $path;
        $renderedTemplate = ob_get_contents();
        ob_end_clean();
        return $renderedTemplate;
    }
}
