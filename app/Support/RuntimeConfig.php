<?php

namespace App\Support;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class RuntimeConfig
{
    /**
     * Merge general settings from the database into runtime config.
     * Avoids writing to config/*.php on production (Coolify/Docker read-only filesystem).
     */
    public static function applyGeneralSettings(): void
    {
        try {
            if (Cache::has('app.enable_clockin_clockout')) {
                Config::set('variable.enable_clockin_clockout', (bool) Cache::get('app.enable_clockin_clockout'));
            }

            if (Cache::has('app.enable_early_clockin')) {
                Config::set('variable.enable_early_clockin', Cache::get('app.enable_early_clockin'));
            }

            if (! Schema::hasTable('general_settings')) {
                return;
            }

            $settings = GeneralSetting::query()->latest('id')->first();

            if (! $settings) {
                return;
            }

            if ($settings->currency) {
                Config::set('variable.currency', $settings->currency);
            }

            if ($settings->currency_format) {
                Config::set('variable.currency_format', $settings->currency_format);
            }

            if ($settings->default_payment_bank) {
                Config::set('variable.account_id', (int) $settings->default_payment_bank);
            }

            if ($settings->date_format) {
                Config::set('variable.date_format', $settings->date_format);

                $jsFormat = config('date_format_conversion.'.$settings->date_format);

                if ($jsFormat) {
                    Config::set('variable.date_format_js', $jsFormat);
                }
            }
        } catch (\Throwable $e) {
            // Database may be unavailable during deploy/migrate; keep env/file defaults.
        }
    }

    public static function setClockInEnabled(bool $enabled): void
    {
        Cache::forever('app.enable_clockin_clockout', $enabled);
        Config::set('variable.enable_clockin_clockout', $enabled);
    }

    public static function setEarlyClockIn(?string $value): void
    {
        Cache::forever('app.enable_early_clockin', $value);
        Config::set('variable.enable_early_clockin', $value);
    }
}
