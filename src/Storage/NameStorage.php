<?php

namespace App\Storage;

class NameStorage
{
    private string $storageFile;

    public function __construct(string $storageFile = 'storage/latest_name.json')
    {
        $this->storageFile = $storageFile;
        $this->ensureStorageDirectoryExists();
    }

    private function ensureStorageDirectoryExists(): void
    {
        $directory = dirname($this->storageFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    public function save(string $name): void
    {
        $data = [
            'latest_name' => $name,
            'generated_at' => date('Y-m-d H:i:s')
        ];

        file_put_contents($this->storageFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function getLatest(): ?string
    {
        if (!file_exists($this->storageFile)) {
            return null;
        }

        $data = json_decode(file_get_contents($this->storageFile), true);
        return $data['latest_name'] ?? null;
    }

    public function clear(): void
    {
        if (file_exists($this->storageFile)) {
            unlink($this->storageFile);
        }
    }
}