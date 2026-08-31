<?php

declare(strict_types=1);

namespace App\Landing\Application\Controllers;

use App\Landing\Contracts\CorrespondingSourceArchive;
use App\Landing\Infrastructure\Service\LegalDocumentReader;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class LegalController
{
    public function __construct(
        private readonly LegalDocumentReader $documents,
        private readonly CorrespondingSourceArchive $archiver,
    ) {}

    public function license(): View
    {
        return view('landing::legal', [
            'title' => 'License — vatrapi',
            'heading' => 'GNU Affero General Public License',
            'lede' => 'vatrapi is free software under AGPL-3.0, including the Swiss Ephemeris calculation engine.',
            'plaintext' => $this->documents->license(),
        ]);
    }

    public function notice(): View
    {
        return view('landing::legal', [
            'title' => 'Notice — vatrapi',
            'heading' => 'Copyright notices',
            'lede' => 'Attribution for vatrapi and the bundled Swiss Ephemeris sources. These notices stay on every copy.',
            'plaintext' => $this->documents->notice(),
        ]);
    }

    public function source(): View
    {
        $repository = config('landing.source_repository_url');

        return view('landing::source', [
            'sourceRepositoryUrl' => is_string($repository) && $repository !== '' ? $repository : null,
        ]);
    }

    public function archive(): BinaryFileResponse
    {
        $path = $this->archiver->build();

        return response()
            ->download($path, 'vatrapi-corresponding-source.tar.gz', [
                'Content-Type' => 'application/gzip',
            ])
            ->deleteFileAfterSend();
    }
}
