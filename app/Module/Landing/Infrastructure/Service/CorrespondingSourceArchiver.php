<?php

declare(strict_types=1);

namespace App\Landing\Infrastructure\Service;

use App\Landing\Contracts\CorrespondingSourceArchive;
use App\Landing\Exception\SourceArchiveFailedException;
use Illuminate\Support\Facades\Process;

final class CorrespondingSourceArchiver implements CorrespondingSourceArchive
{
    /**
     * Generated trees and secrets that are not Corresponding Source.
     *
     * @var list<string>
     */
    private const EXCLUDES = [
        '.git',
        '.env',
        '.env.backup',
        '.env.production',
        'vendor',
        'node_modules',
        'storage',
        '.mcp',
        '.phpunit.cache',
        '.phpunit.result.cache',
        'bootstrap/cache',
        'draft',
        'auth.json',
        'public/hot',
        'public/storage',
        '.idea',
        '.vscode',
        '.nova',
        '.zed',
        '.codex',
    ];

    public function __construct(private readonly ?string $root = null) {}

    public function build(): string
    {
        $destination = sys_get_temp_dir().'/vatrapi-src-'.bin2hex(random_bytes(8)).'.tar.gz';
        $result = Process::timeout(180)->run($this->arguments($destination));

        $size = is_file($destination) ? filesize($destination) : false;

        if ($result->failed() || $size === false || $size === 0) {
            if (is_file($destination)) {
                unlink($destination);
            }

            throw SourceArchiveFailedException::because(trim($result->errorOutput()));
        }

        return $destination;
    }

    /**
     * @return list<string>
     */
    public function arguments(string $destination): array
    {
        $arguments = ['tar', '-czf', $destination, '-C', $this->root()];

        foreach (self::EXCLUDES as $exclude) {
            $arguments[] = '--exclude='.$exclude;
        }

        $arguments[] = '.';

        return $arguments;
    }

    /**
     * @return list<string>
     */
    public function excludes(): array
    {
        return self::EXCLUDES;
    }

    private function root(): string
    {
        return $this->root ?? base_path();
    }
}
