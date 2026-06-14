<?php

namespace App\Http\Controllers\Filebase;

use Illuminate\Routing\Controller as BaseController;

class FilebaseController extends BaseController
{
    private static string $path;

    private static function getPath(): string
    {
        if (! isset(self::$path)) {
            self::$path = base_path('database/filebase/preferences.json');
        }

        return self::$path;
    }

    private static function readAll(): array
    {
        $path = self::getPath();

        if (! file_exists($path)) {
            return self::defaults();
        }

        $data = json_decode(file_get_contents($path), true);

        return is_array($data) ? array_merge(self::defaults(), $data) : self::defaults();
    }

    private static function defaults(): array
    {
        return [
            'season' => date('Y').'/'.(date('Y') + 1),
            'show_inactive_leagues' => false,
            'favorite_teams' => '',
            'favorite_leagues' => '',
        ];
    }

    private static function writeAll(array $data): void
    {
        $path = self::getPath();
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    public static function getField(string $field): array|string|bool
    {
        $data = self::readAll();
        $value = $data[$field] ?? null;

        if (str_contains($field, 'favorite')) {
            $list = explode(',', (string) $value);

            return $list === [''] ? [] : $list;
        }

        return $value ?? '';
    }

    public static function setField(string $field, mixed $value): void
    {
        $data = self::readAll();
        $data[$field] = $value;

        self::writeAll($data);
    }
}
