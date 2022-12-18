<?php

namespace App\Oidcs;

interface OidcInterface
{
    public function thumbprint(): string;

    public function url(): string;

    public function audience(): string;

    public function claim(): string;
}
