<?php

namespace Tests\Feature;

use Tests\TestCase;

class ComposerLocalScriptsTest extends TestCase
{
    public function test_serve_local_script_exists_and_only_serves_backend(): void
    {
        $scripts = $this->composerScripts();
        $serveLocal = $this->scriptText($scripts['serve:local'] ?? null);

        $this->assertNotSame('', $serveLocal);
        $this->assertStringContainsString('artisan serve', $serveLocal);
        $this->assertStringContainsString('--host=127.0.0.1', $serveLocal);
        $this->assertStringContainsString('--port=8001', $serveLocal);
        $this->assertSafeDailyScript($serveLocal);
    }

    public function test_composer_dev_is_safe_daily_startup(): void
    {
        $scripts = $this->composerScripts();
        $dev = $this->scriptText($scripts['dev'] ?? null);

        $this->assertNotSame('', $dev);
        $this->assertStringContainsString('@serve:local', $dev);
        $this->assertSafeDailyScript($dev);
    }

    public function test_local_setup_remains_explicit_setup_command(): void
    {
        $scripts = $this->composerScripts();
        $localSetup = $this->scriptText($scripts['local:setup'] ?? null);

        $this->assertStringContainsString('cms:local-setup', $localSetup);
    }

    public function test_phpunit_does_not_target_local_development_database(): void
    {
        $phpunitXml = file_get_contents(base_path('phpunit.xml'));

        $this->assertIsString($phpunitXml);
        $this->assertStringContainsString('name="DB_DATABASE" value="CMS_TEST"', $phpunitXml);
        $this->assertStringNotContainsString('name="DB_DATABASE" value="CMS"', $phpunitXml);
    }

    /**
     * @return array<string, mixed>
     */
    private function composerScripts(): array
    {
        $composer = json_decode(
            file_get_contents(base_path('composer.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        return $composer['scripts'] ?? [];
    }

    private function scriptText(mixed $script): string
    {
        if (is_array($script)) {
            return implode("\n", $script);
        }

        return is_string($script) ? $script : '';
    }

    private function assertSafeDailyScript(string $script): void
    {
        $forbidden = [
            'migrate',
            'migrate:fresh',
            'fresh',
            'db:seed',
            'seed',
            'cms:local-setup',
            'cms:dev',
            'passport:keys',
            'truncate',
            'delete',
            'drop',
        ];

        foreach ($forbidden as $needle) {
            $this->assertStringNotContainsString($needle, $script);
        }
    }
}
