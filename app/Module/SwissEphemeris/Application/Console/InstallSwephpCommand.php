<?php

declare(strict_types=1);

namespace App\SwissEphemeris\Application\Console;

use App\SwissEphemeris\Infrastructure\Service\SwephpExtensionInstaller;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

#[Signature('swephp:install {--force : Rebuild even if swephp is already loaded} {--sudo : Use sudo for conf.d / php.ini writes when not writable} {--skip-env : Do not write SWISS_EPHEMERIS_EPHE_PATH into .env}')]
#[Description('Build and enable the vendored swephp PHP extension for native (non-Docker) installs')]
final class InstallSwephpCommand extends Command
{
    public function handle(SwephpExtensionInstaller $installer): int
    {
        $this->components->info('Swiss Ephemeris PHP extension (swephp)');

        if ($installer->isLoaded() && ! $this->option('force')) {
            if (! $installer->isEnabledForCli()) {
                $this->components->warn('swephp is loaded in this process, but a fresh CLI PHP does not load it. Re-run with --force.');

                return self::FAILURE;
            }

            $this->components->success('swephp is already loaded for '.PHP_BINARY);
            $this->ensureEpheEnv($installer);
            $this->warnAboutWebSapi();

            return self::SUCCESS;
        }

        if (! $installer->sourceExists()) {
            $this->components->error('Vendored sources missing at '.$installer->sourcePath());

            return self::FAILURE;
        }

        $missing = $installer->missingTools();
        if ($missing !== []) {
            $this->components->error('Missing build tools: '.implode(', ', $missing));
            $this->line('  macOS:   brew install php');
            $this->line('  Debian:  sudo apt install php-dev build-essential');
            $this->line('  Fedora:  sudo dnf install php-devel make gcc');

            return self::FAILURE;
        }

        $this->line('  source:  '.$installer->sourcePath());
        $this->line('  ephe:    '.$installer->ephePath());
        $this->line('  ext dir: '.$installer->extensionDirectory());
        $scan = $installer->iniScanDirectory();
        $this->line('  ini dir: '.($scan ?? '(none — enable extension manually)'));
        $this->line('  php:     '.PHP_BINARY);

        try {
            $iniPath = $installer->install(
                force: (bool) $this->option('force'),
                useSudo: (bool) $this->option('sudo'),
            );
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Enabled via', $iniPath);

        $localIni = $installer->localIniPath();
        if ($iniPath !== $localIni && is_file($localIni)) {
            $this->components->twoColumnDetail('Local copy', $localIni);
        }

        $this->ensureEpheEnv($installer);
        $this->components->success('swephp is enabled for CLI PHP ('.PHP_BINARY.').');
        $this->warnAboutWebSapi();
        $this->line('  Verify:  php -m | grep swephp');

        return self::SUCCESS;
    }

    private function warnAboutWebSapi(): void
    {
        $this->components->warn('If the site runs under php-fpm / Apache / FrankenPHP, restart that SAPI — it may use a different php.ini than CLI.');
    }

    private function ensureEpheEnv(SwephpExtensionInstaller $installer): void
    {
        if ($this->option('skip-env')) {
            return;
        }

        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            return;
        }

        $ephe = $installer->ephePath();
        $assignment = 'SWISS_EPHEMERIS_EPHE_PATH='.$this->envValue($ephe);
        $contents = File::get($envPath);

        if (preg_match('/^SWISS_EPHEMERIS_EPHE_PATH=/m', $contents) === 1) {
            $contents = preg_replace(
                '/^SWISS_EPHEMERIS_EPHE_PATH=.*$/m',
                $assignment,
                $contents,
            ) ?? $contents;
        } else {
            $contents = rtrim($contents)."\n\n{$assignment}\n";
        }

        File::put($envPath, $contents);
        $this->components->twoColumnDetail('SWISS_EPHEMERIS_EPHE_PATH', $ephe);
    }

    private function envValue(string $value): string
    {
        if ($value === '' || preg_match('/[\s#\'"$\\\\]/', $value) === 1) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }
}
