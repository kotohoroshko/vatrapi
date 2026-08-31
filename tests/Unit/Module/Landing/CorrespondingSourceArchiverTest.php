<?php

declare(strict_types=1);

namespace Tests\Unit\Module\Landing;

use App\Landing\Infrastructure\Service\CorrespondingSourceArchiver;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class CorrespondingSourceArchiverTest extends TestCase
{
    public function test_excludes_secrets_and_generated_trees(): void
    {
        $excludes = (new CorrespondingSourceArchiver)->excludes();

        $this->assertContains('.env', $excludes);
        $this->assertContains('vendor', $excludes);
        $this->assertContains('.git', $excludes);
        $this->assertContains('.mcp', $excludes);
        $this->assertContains('storage', $excludes);
        $this->assertNotContains('Docker', $excludes);
        $this->assertNotContains('app', $excludes);
        $this->assertNotContains('.env.example', $excludes);
    }

    public function test_archive_includes_source_and_omits_dotenv(): void
    {
        $root = sys_get_temp_dir().'/vatrapi-agpl-'.bin2hex(random_bytes(4));
        mkdir($root.'/Docker/sweph/src', 0777, true);
        mkdir($root.'/app', 0777, true);
        mkdir($root.'/vendor/pkg', 0777, true);
        mkdir($root.'/storage/logs', 0777, true);

        file_put_contents($root.'/.env', "APP_KEY=secret\n");
        file_put_contents($root.'/.env.example', "APP_KEY=\n");
        file_put_contents($root.'/LICENSE', "GNU AFFERO\n");
        file_put_contents($root.'/Docker/sweph/src/LICENSE', "Swiss Ephemeris\n");
        file_put_contents($root.'/app/hi.php', "<?php\n");
        file_put_contents($root.'/vendor/pkg/secret.php', "nope\n");
        file_put_contents($root.'/storage/logs/laravel.log', "log\n");

        $archive = null;

        try {
            $archive = (new CorrespondingSourceArchiver($root))->build();
            $listing = Process::run(['tar', '-tzf', $archive])->output();
            $files = array_values(array_filter(explode("\n", trim($listing))));

            $this->assertNotContains('./.env', $files);
            $this->assertNotContains('.env', $files);
            $this->assertTrue($this->listingContains($files, '.env.example'));
            $this->assertTrue($this->listingContains($files, 'LICENSE'));
            $this->assertTrue($this->listingContains($files, 'Docker/sweph/src/LICENSE'));
            $this->assertTrue($this->listingContains($files, 'app/hi.php'));
            $this->assertFalse($this->listingContains($files, 'vendor/pkg/secret.php'));
            $this->assertFalse($this->listingContains($files, 'storage/logs/laravel.log'));
        } finally {
            if (is_string($archive) && is_file($archive)) {
                unlink($archive);
            }

            $this->removeDirectory($root);
        }
    }

    /**
     * @param  list<string>  $files
     */
    private function listingContains(array $files, string $needle): bool
    {
        foreach ($files as $file) {
            if ($file === $needle || $file === './'.$needle || str_ends_with($file, '/'.$needle)) {
                return true;
            }
        }

        return false;
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $path = $file->getPathname();

            if ($file->isDir()) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
