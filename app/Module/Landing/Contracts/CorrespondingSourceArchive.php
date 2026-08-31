<?php

declare(strict_types=1);

namespace App\Landing\Contracts;

interface CorrespondingSourceArchive
{
    public function build(): string;
}
