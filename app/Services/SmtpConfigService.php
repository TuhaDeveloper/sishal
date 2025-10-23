<?php

namespace App\Services;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Config;

class SmtpConfigService
{
    /**
     * Configure SMTP settings from database
     */
    public static function configureFromSettings()
    {
        $settings = GeneralSetting::first();
        
        if ($settings && $settings->smtp_host) {
            Config::set([
                'mail.mailers.smtp.host' => $settings->smtp_host,
                'mail.mailers.smtp.port' => $settings->smtp_port ?? 587,
                'mail.mailers.smtp.username' => $settings->smtp_username,
                'mail.mailers.smtp.password' => $settings->smtp_password,
                'mail.mailers.smtp.encryption' => $settings->smtp_encryption ?: null,
                'mail.from.address' => $settings->smtp_from_address ?: $settings->smtp_username,
                'mail.from.name' => $settings->smtp_from_name ?: config('app.name'),
            ]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get contact email from settings
     * Priority: SMTP username > Contact email > fallback
     */
    public static function getContactEmail()
    {
        $settings = GeneralSetting::first();
        
        // First priority: Use SMTP username (if configured)
        if ($settings && $settings->smtp_username) {
            return $settings->smtp_username;
        }
        
        // Second priority: Use contact email from Contact Info tab
        if ($settings && $settings->contact_email) {
            return $settings->contact_email;
        }
        
        // Fallback
        return 'noreply@' . parse_url(config('app.url'), PHP_URL_HOST) ?: 'example.com';
    }
    
    /**
     * Check if SMTP is configured
     */
    public static function isConfigured()
    {
        $settings = GeneralSetting::first();
        return $settings && $settings->smtp_host && $settings->smtp_username && $settings->smtp_password;
    }
}
