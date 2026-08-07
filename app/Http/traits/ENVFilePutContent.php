<?php

namespace App\Http\traits;

use Illuminate\Support\Facades\Log;

trait ENVFilePutContent
{
    public function dataWriteInENVFile($key, $value): bool
    {
        $path = base_path('.env');

        if (! is_file($path)) {
            if (! @touch($path)) {
                Log::warning('ENV update skipped: .env file is missing and cannot be created.', ['key' => $key]);

                return false;
            }
        }

        if (! is_writable($path)) {
            Log::warning('ENV update skipped: .env is not writable.', ['key' => $key]);

            return false;
        }

        $content = (string) file_get_contents($path);
        $formattedValue = $this->formatEnvValue($value);

        if (preg_match("/^{$key}=.*/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$formattedValue}", $content);
        } else {
            $content .= PHP_EOL."{$key}={$formattedValue}";
        }

        if (@file_put_contents($path, $content) === false) {
            Log::warning('ENV update failed while writing .env.', ['key' => $key]);

            return false;
        }

        return true;
    }

    /**
     * Always wrap .env values in double quotes so spaces / special chars
     * (mail passwords, from names, white-label titles, etc.) parse safely.
     */
    protected function formatEnvValue($value): string
    {
        if ($value === null) {
            return '""';
        }

        $value = str_replace(
            ['\\', '"', "\r", "\n"],
            ['\\\\', '\\"', '', ''],
            (string) $value
        );

        return '"'.$value.'"';
    }
}
