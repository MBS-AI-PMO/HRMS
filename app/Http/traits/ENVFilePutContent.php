<?php
namespace App\Http\traits;

trait ENVFilePutContent
{
    public function dataWriteInENVFile($key, $value)
    {
        $path = base_path('.env');

        // Check if .env exists; if not, create an empty one
        if (!file_exists($path)) {
            file_put_contents($path, '');
        }

        // Read content safely
        $content = file_get_contents($path);

        // Replace key if exists, or append
        if (str_contains($content, "{$key}=")) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= PHP_EOL . "{$key}={$value}";
        }

        // Save updated content
        file_put_contents($path, $content);

        return true;
    }
}
