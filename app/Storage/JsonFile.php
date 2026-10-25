<?php

namespace App\Storage;

use RuntimeException;

final class JsonFile
{
    /**
     * @return array<mixed>|null
     */
    public static function read(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            throw new RuntimeException("JSON inválido em {$path}");
        }

        return $data;
    }

    /**
     * Grava só se o conteúdo mudou (evita commits vazios). Escrita atômica.
     *
     * @param  array<mixed>  $data
     */
    public static function write(string $path, array $data): bool
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";

        if (is_file($path) && file_get_contents($path) === $json) {
            return false;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $tmp = $path.'.tmp';
        file_put_contents($tmp, $json);
        rename($tmp, $path);

        return true;
    }
}
