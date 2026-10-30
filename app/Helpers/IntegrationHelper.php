<?php

namespace App\Helpers;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Schema;

class IntegrationHelper
{
    protected static ?IntegrationSetting $settings = null;

    protected static function getSettings(): IntegrationSetting
    {
        if (self::$settings === null) {
            try {
                if (Schema::hasTable('integration_settings')) {
                    self::$settings = IntegrationSetting::first() ?? IntegrationSetting::create([
                        'social_media_integration' => true,
                        'whatsapp_integration' => true,
                        'email_smtp' => true,
                        'google_connection' => true,
                    ]);
                }
            } catch (\Throwable $e) {
                // Return default fallback if table is not migrated yet or database is not available
            }

            if (self::$settings === null) {
                self::$settings = new IntegrationSetting([
                    'social_media_integration' => true,
                    'whatsapp_integration' => true,
                    'email_smtp' => true,
                    'google_connection' => true,
                ]);
            }
        }

        return self::$settings;
    }

    public static function isSocialMediaEnabled(): bool
    {
        return (bool) self::getSettings()->social_media_integration;
    }

    public static function isWhatsAppEnabled(): bool
    {
        return (bool) self::getSettings()->whatsapp_integration;
    }

    public static function isEmailEnabled(): bool
    {
        return (bool) self::getSettings()->email_smtp;
    }

    public static function isGoogleEnabled(): bool
    {
        return (bool) self::getSettings()->google_connection;
    }

    public static function clearCache(): void
    {
        self::$settings = null;
    }
}
