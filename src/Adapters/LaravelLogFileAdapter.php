<?php

namespace Magentron\LaravelFirewallFilament\Adapters;

use Illuminate\Support\Collection;

class LaravelLogFileAdapter implements LogSourceAdapter
{
    private const MAX_SCAN_BYTES = 262144; // 256 KiB
    private const MAX_LINE_LENGTH = 4096;
    private const PREFIX = 'FIREWALL:';

    /**
     * Process-lifetime cache for resolved paths.
     *
     * Useful for repeated checks in a single worker/request lifecycle. In long-lived
     * runtimes (for example Octane workers), updates to filesystem paths are not
     * reflected until the process is restarted.
     *
     * @var array<string, string|false>
     */
    private static array $realpathCache = [];

    private readonly ?string $resolvedPath;

    /**
     * @param array<int, string> $allowlist Absolute paths that are permitted.
     */
    public function __construct(
        string $logPath,
        array $allowlist = [],
    ) {
        $this->resolvedPath = $this->resolveAgainstAllowlist($logPath, $allowlist);
    }

    public function supported(): bool
    {
        return $this->resolvedPath !== null
            && is_file($this->resolvedPath)
            && is_readable($this->resolvedPath);
    }

    private function resolveAgainstAllowlist(string $logPath, array $allowlist): ?string
    {
        if ($logPath === '' || $allowlist === []) {
            return null;
        }

        $real = $this->cachedRealpath($logPath);

        if ($real === false) {
            return null;
        }

        foreach ($allowlist as $allowed) {
            if (! is_string($allowed) || $allowed === '') {
                continue;
            }

            $allowedReal = $this->cachedRealpath($allowed);

            if ($allowedReal !== false && $allowedReal === $real) {
                return $real;
            }
        }

        return null;
    }

    private function cachedRealpath(string $path): string|false
    {
        if (! array_key_exists($path, self::$realpathCache)) {
            self::$realpathCache[$path] = realpath($path);
        }

        return self::$realpathCache[$path];
    }

    public function recentEntries(int $limit): iterable
    {
        if (! $this->supported()) {
            return new Collection();
        }

        $fileSize = filesize($this->resolvedPath);

        if ($fileSize === false || $fileSize === 0) {
            return new Collection();
        }

        $handle = fopen($this->resolvedPath, 'rb');

        if ($handle === false) {
            return new Collection();
        }

        try {
            $offset = max(0, $fileSize - self::MAX_SCAN_BYTES);
            fseek($handle, $offset);

            if ($offset > 0) {
                fgets($handle);
            }

            $entries = [];

            while (($line = fgets($handle)) !== false) {
                $line = substr(trim($line), 0, self::MAX_LINE_LENGTH);

                if (str_contains($line, self::PREFIX)) {
                    $entries[] = $line;
                }
            }

            return new Collection(array_slice($entries, -$limit));
        } finally {
            fclose($handle);
        }
    }
}
