<?php

namespace Magentron\LaravelFirewallFilament\Adapters;

use Illuminate\Support\Collection;

class LaravelLogFileAdapter implements LogSourceAdapter
{
    private const MAX_SCAN_BYTES = 262144; // 256 KiB
    private const MAX_LINE_LENGTH = 4096;
    private const PREFIX = 'FIREWALL:';

    public function __construct(
        private readonly string $logPath,
    ) {
    }

    public function supported(): bool
    {
        return $this->logPath !== '' && is_file($this->logPath) && is_readable($this->logPath);
    }

    public function recentEntries(int $limit): iterable
    {
        if (! $this->supported()) {
            return new Collection();
        }

        $fileSize = filesize($this->logPath);

        if ($fileSize === false || $fileSize === 0) {
            return new Collection();
        }

        $handle = fopen($this->logPath, 'r');

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
