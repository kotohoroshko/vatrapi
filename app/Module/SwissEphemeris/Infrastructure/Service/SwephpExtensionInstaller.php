<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Infrastructure\Service;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Builds and enables the vendored swephp PHP extension from Docker/sweph.
 */
class SwephpExtensionInstaller
{
    public function __construct(
        private readonly string $sourcePath,
        private readonly string $ephePath,
        private readonly string $localExtensionPath,
    ) {}

    public function isLoaded(): bool
    {
        return extension_loaded('swephp');
    }

    /**
     * @return list<string>
     */
    public function missingTools(): array
    {
        $missing = [];

        foreach (['phpize', 'php-config', 'make', 'cc'] as $tool) {
            if (! $this->commandExists($tool)) {
                $missing[] = $tool;
            }
        }

        return $missing;
    }

    public function sourceExists(): bool
    {
        return is_dir($this->sourcePath)
            && is_file($this->sourcePath.'/config.m4')
            && is_file($this->sourcePath.'/swephp.c');
    }

    public function epheExists(): bool
    {
        return is_dir($this->ephePath);
    }

    public function extensionDirectory(): string
    {
        return rtrim($this->run(['php-config', '--extension-dir'], timeout: 30), "\n");
    }

    public function iniScanDirectory(): ?string
    {
        $output = $this->run([PHP_BINARY, '--ini'], timeout: 30);

        if (preg_match('/Scan for additional \.ini files in:\s*(.+)/', $output, $matches) !== 1) {
            return null;
        }

        $dir = trim($matches[1], " \t\"'");

        if ($dir === '' || strcasecmp($dir, '(none)') === 0) {
            return null;
        }

        return $dir;
    }

    public function loadedIniFile(): ?string
    {
        $output = $this->run([PHP_BINARY, '--ini'], timeout: 30);

        if (preg_match('/Loaded Configuration File:\s*(.+)/', $output, $matches) !== 1) {
            return null;
        }

        $file = trim($matches[1], " \t\"'");

        if ($file === '' || strcasecmp($file, '(none)') === 0) {
            return null;
        }

        return $file;
    }

    /**
     * Build, install, and enable swephp for the current PHP_BINARY.
     *
     * @throws RuntimeException
     */
    public function install(bool $force = false, bool $useSudo = false): string
    {
        if ($this->isLoaded() && ! $force) {
            return $this->localIniPath();
        }

        if (! $this->sourceExists()) {
            throw new RuntimeException(sprintf(
                'swephp sources not found at %s. Expected the vendored Docker/sweph tree.',
                $this->sourcePath,
            ));
        }

        $missing = $this->missingTools();
        if ($missing !== []) {
            throw new RuntimeException(sprintf(
                'Missing build tools: %s. Install PHP development headers (phpize) and a C toolchain, then retry.',
                implode(', ', $missing),
            ));
        }

        if (! $this->epheExists()) {
            throw new RuntimeException(sprintf(
                'Ephemeris data directory missing: %s',
                $this->ephePath,
            ));
        }

        $this->build();
        $extensionPath = $this->placeExtension();
        $iniPath = $this->enableExtension($extensionPath, $useSudo);
        $this->assertEnabledByDefaultPhp($iniPath, $extensionPath);
        $this->cleanBuildArtifacts();

        return $iniPath;
    }

    public function ephePath(): string
    {
        return $this->ephePath;
    }

    public function sourcePath(): string
    {
        return $this->sourcePath;
    }

    public function localIniPath(): string
    {
        return dirname($this->localExtensionPath).DIRECTORY_SEPARATOR.'99-swephp.ini';
    }

    /**
     * True when a fresh PHP process (no -d) loads swephp.
     */
    public function isEnabledForCli(): bool
    {
        $modules = $this->run([PHP_BINARY, '-m'], timeout: 30, allowFailure: true);

        return preg_match('/^swephp$/m', $modules) === 1;
    }

    private function build(): void
    {
        $this->runInSource(['phpize', '--clean'], timeout: 120, allowFailure: true);
        $this->runInSource(['phpize'], timeout: 120);
        $this->runInSource(['./configure'], timeout: 300);
        $this->runInSource(['make'], timeout: 600);
    }

    private function placeExtension(): string
    {
        $built = $this->sourcePath.'/modules/swephp.so';

        if (! is_file($built)) {
            throw new RuntimeException('Build succeeded but modules/swephp.so was not produced.');
        }

        $localDir = dirname($this->localExtensionPath);
        if (! is_dir($localDir) && ! mkdir($localDir, 0775, true) && ! is_dir($localDir)) {
            throw new RuntimeException("Unable to create {$localDir}");
        }

        if (! copy($built, $this->localExtensionPath)) {
            throw new RuntimeException("Unable to copy swephp.so to {$this->localExtensionPath}");
        }

        return $this->localExtensionPath;
    }

    private function enableExtension(string $extensionPath, bool $useSudo): string
    {
        $contents = "; Generated by php artisan swephp:install\nextension={$extensionPath}\n";
        $localIni = $this->localIniPath();
        file_put_contents($localIni, $contents);

        $scanDir = $this->iniScanDirectory();
        if ($scanDir !== null) {
            $scanIni = rtrim($scanDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'99-swephp.ini';

            if ($this->ensureDirectory($scanDir, $useSudo) && $this->writeFile($scanIni, $contents, $useSudo)) {
                return $scanIni;
            }
        }

        $loadedIni = $this->loadedIniFile();
        if ($loadedIni !== null && is_file($loadedIni) && $this->appendExtensionLine($loadedIni, $extensionPath, $useSudo)) {
            return $loadedIni;
        }

        throw new RuntimeException(implode("\n", [
            "Built {$extensionPath}, but could not enable it for default PHP ({$this->phpIdentity()}).",
            "Wrote a local drop-in at {$localIni} (not auto-loaded).",
            'Fix one of:',
            '  1) php artisan swephp:install --sudo',
            '  2) Create '.($scanDir ? rtrim($scanDir, DIRECTORY_SEPARATOR).'/99-swephp.ini' : 'a conf.d drop-in').' with:',
            "       extension={$extensionPath}",
            '  3) Or: export PHP_INI_SCAN_DIR=":'.dirname($localIni).'"',
        ]));
    }

    private function ensureDirectory(string $directory, bool $useSudo): bool
    {
        if (is_dir($directory)) {
            return true;
        }

        $parent = dirname($directory);
        if (is_dir($parent) && is_writable($parent)) {
            return mkdir($directory, 0775, true) || is_dir($directory);
        }

        if ($useSudo) {
            try {
                $this->run(array_merge($this->sudoPrefix(), ['mkdir', '-p', $directory]), timeout: 120);

                return is_dir($directory);
            } catch (RuntimeException) {
                return false;
            }
        }

        return false;
    }

    private function writeFile(string $path, string $contents, bool $useSudo): bool
    {
        $directory = dirname($path);

        if (is_writable($directory) || (is_file($path) && is_writable($path))) {
            return file_put_contents($path, $contents) !== false;
        }

        if ($useSudo) {
            try {
                $this->run(array_merge($this->sudoPrefix(), ['tee', $path]), timeout: 120, input: $contents);

                return is_file($path);
            } catch (RuntimeException) {
                return false;
            }
        }

        return false;
    }

    private function appendExtensionLine(string $iniPath, string $extensionPath, bool $useSudo): bool
    {
        $line = 'extension='.$extensionPath;
        $existing = (string) file_get_contents($iniPath);

        // Only treat active (non-commented) lines as already enabled.
        if (preg_match('/^\s*extension\s*=\s*.*swephp/mi', $existing) === 1
            || preg_match('/^\s*'.preg_quote($line, '/').'\s*$/mi', $existing) === 1) {
            return true;
        }

        $append = "\n; Added by php artisan swephp:install\n{$line}\n";

        if (is_writable($iniPath)) {
            return file_put_contents($iniPath, $existing.$append) !== false;
        }

        if ($useSudo) {
            try {
                $this->run(array_merge($this->sudoPrefix(), ['tee', '-a', $iniPath]), timeout: 120, input: $append);

                return true;
            } catch (RuntimeException) {
                return false;
            }
        }

        return false;
    }

    /**
     * Must load via default PHP startup (ini scan / php.ini), not via `php -d`.
     * Using `-d` alone caused false success when only a local non-scanned ini was written.
     */
    private function assertEnabledByDefaultPhp(string $iniPath, string $extensionPath): void
    {
        if ($this->isEnabledForCli()) {
            return;
        }

        throw new RuntimeException(implode("\n", [
            "swephp was written but default `{$this->phpIdentity()} -m` does not load it.",
            "Ini attempted: {$iniPath}",
            "Extension: {$extensionPath}",
            'The web SAPI (php-fpm) may also need the same drop-in — restart it after fixing CLI.',
        ]));
    }

    /**
     * @return list<string>
     */
    private function sudoPrefix(): array
    {
        // Prefer interactive sudo when a TTY is available; -n only for non-interactive CI.
        if (function_exists('posix_isatty') && defined('STDIN') && @posix_isatty(STDIN)) {
            return ['sudo'];
        }

        return ['sudo', '-n'];
    }

    private function phpIdentity(): string
    {
        return PHP_BINARY;
    }

    private function cleanBuildArtifacts(): void
    {
        $this->runInSource(['phpize', '--clean'], timeout: 120, allowFailure: true);
        $this->runInSource(['make', '-C', 'sweph/src', 'clean'], timeout: 120, allowFailure: true);
    }

    private function commandExists(string $command): bool
    {
        return Process::timeout(10)
            ->run('command -v '.escapeshellarg($command))
            ->successful();
    }

    /**
     * @param  list<string>  $command
     */
    private function runInSource(array $command, int $timeout, bool $allowFailure = false, ?string $input = null): string
    {
        return $this->run($command, $timeout, $allowFailure, $input, $this->sourcePath);
    }

    /**
     * @param  list<string>  $command
     */
    private function run(
        array $command,
        int $timeout,
        bool $allowFailure = false,
        ?string $input = null,
        ?string $path = null,
    ): string {
        $shell = implode(' ', array_map(static fn (string $part): string => escapeshellarg($part), $command));

        /** @var PendingProcess $pending */
        $pending = Process::timeout($timeout);

        if ($path !== null) {
            $pending = $pending->path($path);
        }

        if ($input !== null) {
            $pending = $pending->input($input);
        }

        $result = $pending->run($shell);

        if (! $result->successful() && ! $allowFailure) {
            $error = trim($result->errorOutput()."\n".$result->output());

            throw new RuntimeException(sprintf(
                'Command failed (%s): %s',
                implode(' ', $command),
                $error !== '' ? $error : 'exit '.$result->exitCode(),
            ));
        }

        return $result->output().$result->errorOutput();
    }
}
