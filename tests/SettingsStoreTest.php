<?php

namespace Magentron\LaravelFirewallFilament\Tests;

use Magentron\LaravelFirewallFilament\Support\SettingsStore;
use PHPUnit\Framework\TestCase;

class SettingsStoreTest extends TestCase
{
    private string $tempDir;
    private string $settingsFile;
    private string $snapshotDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/firewall-filament-test-' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->settingsFile = $this->tempDir . '/settings.json';
        $this->snapshotDir = $this->tempDir . '/snapshots';
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
        parent::tearDown();
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function makeStore(): SettingsStore
    {
        return new SettingsStore($this->settingsFile, $this->snapshotDir);
    }

    public function test_get_returns_empty_when_no_file(): void
    {
        $store = $this->makeStore();
        $this->assertSame([], $store->get());
    }

    public function test_save_creates_file(): void
    {
        $store = $this->makeStore();
        $store->save([
            'firewall.enable_log' => true,
            'firewall.log_stack' => 'daily',
        ]);

        $this->assertFileExists($this->settingsFile);
        $data = json_decode(file_get_contents($this->settingsFile), true);
        $this->assertTrue($data['firewall.enable_log']);
        $this->assertSame('daily', $data['firewall.log_stack']);
    }

    public function test_get_returns_saved_settings(): void
    {
        $store = $this->makeStore();
        $store->save([
            'firewall.enable_log' => false,
            'firewall.log_stack' => 'single',
        ]);

        $result = $store->get();
        $this->assertFalse($result['firewall.enable_log']);
        $this->assertSame('single', $result['firewall.log_stack']);
    }

    public function test_save_filters_out_non_writable_keys(): void
    {
        $store = $this->makeStore();
        $store->save([
            'firewall.enable_log' => true,
            'firewall.log_stack' => 'stack',
            'firewall.use_database' => true,
            'some.other.key' => 'malicious',
        ]);

        $result = $store->get();
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('firewall.enable_log', $result);
        $this->assertArrayHasKey('firewall.log_stack', $result);
        $this->assertArrayNotHasKey('firewall.use_database', $result);
        $this->assertArrayNotHasKey('some.other.key', $result);
    }

    public function test_save_creates_snapshot_of_previous(): void
    {
        $store = $this->makeStore();

        $store->save(['firewall.enable_log' => true, 'firewall.log_stack' => 'stack']);
        $store->save(['firewall.enable_log' => false, 'firewall.log_stack' => 'daily']);

        $snapshots = $store->snapshots();
        $this->assertCount(1, $snapshots);
        $this->assertTrue($snapshots[0]['settings']['firewall.enable_log']);
        $this->assertSame('stack', $snapshots[0]['settings']['firewall.log_stack']);
    }

    public function test_no_snapshot_on_first_save(): void
    {
        $store = $this->makeStore();
        $store->save(['firewall.enable_log' => true, 'firewall.log_stack' => null]);

        $this->assertSame([], $store->snapshots());
    }

    public function test_snapshots_are_ordered_newest_first(): void
    {
        $store = $this->makeStore();

        $store->save(['firewall.enable_log' => true, 'firewall.log_stack' => 'a']);
        usleep(1100000); // >1s to get different timestamps
        $store->save(['firewall.enable_log' => false, 'firewall.log_stack' => 'b']);
        usleep(1100000);
        $store->save(['firewall.enable_log' => true, 'firewall.log_stack' => 'c']);

        $snapshots = $store->snapshots();
        $this->assertCount(2, $snapshots);
        $this->assertSame('b', $snapshots[0]['settings']['firewall.log_stack']);
        $this->assertSame('a', $snapshots[1]['settings']['firewall.log_stack']);
    }

    public function test_restore_applies_snapshot(): void
    {
        $store = $this->makeStore();

        $store->save(['firewall.enable_log' => true, 'firewall.log_stack' => 'original']);
        usleep(1100000);
        $store->save(['firewall.enable_log' => false, 'firewall.log_stack' => 'changed']);

        $snapshots = $store->snapshots();
        $store->restore($snapshots[0]['id']);

        $current = $store->get();
        $this->assertTrue($current['firewall.enable_log']);
        $this->assertSame('original', $current['firewall.log_stack']);
    }

    public function test_restore_invalid_snapshot_throws(): void
    {
        $store = $this->makeStore();

        $this->expectException(\InvalidArgumentException::class);
        $store->restore('nonexistent-snapshot');
    }

    public function test_prune_keeps_max_snapshots(): void
    {
        $store = $this->makeStore();

        // Create MAX_SNAPSHOTS + 2 saves (first save creates no snapshot)
        for ($i = 0; $i < SettingsStore::MAX_SNAPSHOTS + 2; $i++) {
            // Write unique timestamps by manipulating files directly for speed
            $store->save([
                'firewall.enable_log' => ($i % 2 === 0),
                'firewall.log_stack' => "channel-{$i}",
            ]);

            // Rename snapshot to ensure unique name (avoid same-second collisions)
            if (is_dir($this->snapshotDir)) {
                $files = glob($this->snapshotDir . '/*.json');
                foreach ($files as $file) {
                    $base = basename($file, '.json');
                    if (!str_contains($base, "iter{$i}")) {
                        $newName = $this->snapshotDir . '/' . $base . "-iter{$i}.json";
                        if (!file_exists($newName)) {
                            rename($file, $newName);
                        }
                    }
                }
            }
        }

        $snapshots = $store->snapshots();
        $this->assertLessThanOrEqual(SettingsStore::MAX_SNAPSHOTS, count($snapshots));
    }

    public function test_writable_keys_constant(): void
    {
        $this->assertSame(
            ['firewall.enable_log', 'firewall.log_stack'],
            SettingsStore::WRITABLE_KEYS
        );
    }

    public function test_get_handles_corrupted_file(): void
    {
        $store = $this->makeStore();

        file_put_contents($this->settingsFile, 'not valid json {{{');

        $this->assertSame([], $store->get());
    }

    public function test_get_settings_file_path(): void
    {
        $store = $this->makeStore();
        $this->assertSame($this->settingsFile, $store->getSettingsFilePath());
    }

    public function test_restore_snapshot_path_traversal_rejected(): void
    {
        $store = $this->makeStore();
        $this->expectException(\InvalidArgumentException::class);
        $store->restore('../../etc/passwd');
    }
}
