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

        if (str_contains($content, "{$key}=")) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= PHP_EOL."{$key}={$value}";
        }

        if (@file_put_contents($path, $content) === false) {
            Log::warning('ENV update failed while writing .env.', ['key' => $key]);

            return false;
        }

        return true;
    }
}
