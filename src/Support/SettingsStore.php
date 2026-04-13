<?php

namespace Magentron\LaravelFirewallFilament\Support;

class SettingsStore
{
    public const WRITABLE_KEYS = ['firewall.enable_log', 'firewall.log_stack'];

    public const MAX_SNAPSHOTS = 10;

    private string $settingsFile;

    private string $snapshotDir;

    public function __construct(string $settingsFile, string $snapshotDir)
    {
        $this->settingsFile = $settingsFile;
        $this->snapshotDir = $snapshotDir;
    }

    public function get(): array
    {
        if (!file_exists($this->settingsFile)) {
            return [];
        }

        $contents = file_get_contents($this->settingsFile);
        if ($contents === false) {
            return [];
        }

        $data = json_decode($contents, true);

        return is_array($data) ? $this->filterKeys($data) : [];
    }

    public function save(array $settings): void
    {
        $filtered = $this->filterKeys($settings);

        $current = $this->get();
        if ($current !== []) {
            $this->createSnapshot($current);
        }

        $dir = dirname($this->settingsFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $this->settingsFile,
            json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            LOCK_EX
        );
    }

    /**
     * @return array<int, array{id: string, date: string, settings: array}>
     */
    public function snapshots(): array
    {
        if (!is_dir($this->snapshotDir)) {
            return [];
        }

        $files = glob($this->snapshotDir . '/*.json');
        if ($files === false) {
            return [];
        }

        rsort($files);

        $snapshots = [];
        foreach ($files as $file) {
            $basename = basename($file, '.json');
            $contents = file_get_contents($file);
            if ($contents === false) {
                continue;
            }
            $data = json_decode($contents, true);
            if (!is_array($data)) {
                continue;
            }

            $snapshots[] = [
                'id' => $basename,
                'date' => $this->parseSnapshotDate($basename),
                'settings' => $data,
            ];
        }

        return $snapshots;
    }

    public function restore(string $snapshotId): array
    {
        $file = $this->snapshotDir . '/' . basename($snapshotId) . '.json';
        if (!file_exists($file)) {
            throw new \InvalidArgumentException("Snapshot '{$snapshotId}' not found.");
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new \RuntimeException("Could not read snapshot '{$snapshotId}'.");
        }

        $data = json_decode($contents, true);
        if (!is_array($data)) {
            throw new \RuntimeException("Snapshot '{$snapshotId}' contains invalid data.");
        }

        $this->save($data);

        return $this->get();
    }

    public function getSettingsFilePath(): string
    {
        return $this->settingsFile;
    }

    private function filterKeys(array $data): array
    {
        return array_intersect_key($data, array_flip(self::WRITABLE_KEYS));
    }

    private function createSnapshot(array $settings): void
    {
        if (!is_dir($this->snapshotDir)) {
            mkdir($this->snapshotDir, 0755, true);
        }

        $timestamp = date('Y-m-d\TH-i-s');
        $file = $this->snapshotDir . '/' . $timestamp . '.json';

        file_put_contents(
            $file,
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            LOCK_EX
        );

        $this->pruneSnapshots();
    }

    private function pruneSnapshots(): void
    {
        $files = glob($this->snapshotDir . '/*.json');
        if ($files === false) {
            return;
        }

        rsort($files);

        $excess = array_slice($files, self::MAX_SNAPSHOTS);
        foreach ($excess as $file) {
            unlink($file);
        }
    }

    private function parseSnapshotDate(string $basename): string
    {
        return str_replace(['T', '-i-', '-s'], ['T', ':', ':'], preg_replace(
            '/^(\d{4}-\d{2}-\d{2})T(\d{2})-(\d{2})-(\d{2})$/',
            '$1 $2:$3:$4',
            $basename
        ) ?? $basename);
    }
}
