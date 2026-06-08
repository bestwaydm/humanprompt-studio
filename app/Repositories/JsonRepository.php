<?php

declare(strict_types=1);

final class JsonRepository
{
    public function read(string $filename, array $fallback = []): array
    {
        $path = data_path($filename);

        if (!is_file($path)) {
            return $fallback;
        }

        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return $fallback;
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : $fallback;
    }

    public function write(string $filename, array $payload): bool
    {
        $path = data_path($filename);
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            return false;
        }

        $handle = fopen($path, 'c+');
        if ($handle === false) {
            return false;
        }

        flock($handle, LOCK_EX);
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, $json);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return true;
    }

    public function prependHistory(array $entry, int $limit = 200): bool
    {
        $history = $this->read('prompt_history.json', []);
        array_unshift($history, $entry);
        $history = array_slice($history, 0, $limit);
        return $this->write('prompt_history.json', $history);
    }
}
