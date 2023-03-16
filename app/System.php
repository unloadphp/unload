<?php

namespace App;

class System
{
    public function open(string $content): string
    {
        $tmpFilePath = tempnam(sys_get_temp_dir(), 'unload'.time());
        file_put_contents($tmpFilePath, $content);
        system("vi {$tmpFilePath} >> `tty`");

        $newContent = file_get_contents($tmpFilePath);
        unlink($tmpFilePath);

        return $newContent;
    }

    public function browser(string $url): int
    {
        return (int) exec("open '$url' 2>/dev/null || xdg-open '$url' 2>/dev/null");
    }
}
