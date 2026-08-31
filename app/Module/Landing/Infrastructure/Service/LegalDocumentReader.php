<?php

declare(strict_types=1);

namespace App\Landing\Infrastructure\Service;

use Illuminate\Support\Facades\File;
use RuntimeException;

final class LegalDocumentReader
{
    public function license(): string
    {
        return $this->read(base_path('LICENSE'));
    }

    public function notice(): string
    {
        return $this->read(base_path('NOTICE'))."\n\n".$this->read(base_path('Docker/sweph/NOTICE.md'));
    }

    private function read(string $path): string
    {
        if (! File::isFile($path)) {
            throw new RuntimeException('Legal document missing: '.$path);
        }

        return File::get($path);
    }
}
