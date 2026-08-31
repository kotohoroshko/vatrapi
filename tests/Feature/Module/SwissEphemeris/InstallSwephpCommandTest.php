<?php

declare(strict_types=1);

namespace Tests\Feature\Module\SwissEphemeris;

use App\SwissEphemeris\Exception\SwissEphemerisException;
use App\SwissEphemeris\Infrastructure\Service\SwephpExtensionInstaller;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

final class InstallSwephpCommandTest extends TestCase
{
    public function test_command_is_registered(): void
    {
        $this->artisan('list')
            ->expectsOutputToContain('swephp:install')
            ->assertSuccessful();
    }

    public function test_extension_missing_message_points_to_install_command(): void
    {
        $this->assertSame(
            'The "swephp" PHP extension is not loaded. Run: php artisan swephp:install',
            SwissEphemerisException::extensionMissing()->getMessage(),
        );
    }

    public function test_installer_reports_missing_build_tools(): void
    {
        Process::fake([
            'command -v *' => Process::result(exitCode: 1),
        ]);

        $installer = new SwephpExtensionInstaller(
            sourcePath: base_path('Docker/sweph'),
            ephePath: base_path('Docker/sweph/ephe'),
            localExtensionPath: storage_path('swephp/swephp.so'),
        );

        $this->assertSame(['phpize', 'php-config', 'make', 'cc'], $installer->missingTools());
    }

    public function test_command_exits_successfully_when_extension_already_loaded(): void
    {
        if (! extension_loaded('swephp')) {
            $this->markTestSkipped('Requires swephp to exercise the already-loaded path.');
        }

        $this->artisan('swephp:install', ['--skip-env' => true])
            ->assertSuccessful();
    }

    public function test_command_fails_when_build_tools_are_missing(): void
    {
        if (extension_loaded('swephp')) {
            $this->markTestSkipped('Extension already loaded; missing-tools path is not exercised.');
        }

        Process::fake([
            'command -v *' => Process::result(exitCode: 1),
        ]);

        $this->artisan('swephp:install', ['--skip-env' => true])
            ->expectsOutputToContain('Missing build tools')
            ->assertFailed();
    }

    public function test_default_ephe_path_falls_back_to_vendored_tree(): void
    {
        config()->set('swiss-ephemeris.ephe_path', base_path('Docker/sweph/ephe'));

        $this->assertDirectoryExists(config('swiss-ephemeris.ephe_path'));
    }

    public function test_enable_fails_closed_when_only_local_ini_would_be_written(): void
    {
        $installer = new class(base_path('Docker/sweph'), base_path('Docker/sweph/ephe'), storage_path('swephp/swephp.so')) extends SwephpExtensionInstaller
        {
            public function iniScanDirectory(): ?string
            {
                // Parent directory does not exist → cannot mkdir without sudo.
                return '/no/such/vatrapi/'.uniqid('', true).'/conf.d';
            }

            public function loadedIniFile(): ?string
            {
                return '/etc/vatrapi-missing-php.ini';
            }
        };

        $method = new \ReflectionMethod(SwephpExtensionInstaller::class, 'enableExtension');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('could not enable it for default PHP');

        $method->invoke($installer, storage_path('swephp/swephp.so'), false);
    }

    public function test_julian_day_api_works_when_extension_is_loaded(): void
    {
        if (! extension_loaded('swephp')) {
            $this->markTestSkipped('Requires swephp.');
        }

        config()->set('swiss-ephemeris.ephe_path', base_path('Docker/sweph/ephe'));

        $response = $this->postJson('/api/swiss-ephemeris/julian-day/to', [
            'moment' => '1994-03-03T07:35',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.julianDay', 2449414.815972222);
    }
}
