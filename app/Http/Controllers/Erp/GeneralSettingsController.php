<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class GeneralSettingsController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasPermissionTo('setting manage')) {
            abort(403, 'Unauthorized action.');
        }
        $settings = GeneralSetting::first();
        return view('erp.settings.settings',compact('settings'));
    }

    public function storeUpdate(Request $request)
    {
        $validated = $request->validate([
            'site_title' => 'nullable|string|max:255',
            'site_description' => 'nullable|string|max:255',
            'top_text' => 'nullable|string|max:255',
            'footer_text' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'contact_address' => 'nullable|string|max:255',
            'website_url' => 'nullable|url|max:255',
            'invoice_prefix' => 'nullable|string|max:10',
            'facebook_url' => 'nullable|string|max:255',
            'x_url' => 'nullable|string|max:255',
            'youtube_url' => 'nullable|string|max:255',
            'instagram_url' => 'nullable|string|max:255',
            'whatsapp_url' => 'nullable|string|max:255',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'site_favicon' => 'nullable|image|mimes:jpeg,png,ico,webp|max:1024',
            'support_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|email|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|string|in:tls,ssl,',
            'smtp_from_address' => 'nullable|email|max:255',
            'smtp_from_name' => 'nullable|string|max:255',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // Get the settings row (create if not exists)
        $settings = GeneralSetting::first() ?? new GeneralSetting();

        // Handle logo upload
        if ($request->hasFile('site_logo')) {
            if ($settings->site_logo && file_exists(public_path($settings->site_logo))) {
                @unlink(public_path($settings->site_logo));
            }
            if (!file_exists(public_path('uploads/settings'))) {
                mkdir(public_path('uploads/settings'), 0777, true);
            }
            $logoFile = $request->file('site_logo');
            $logoName = uniqid('logo_') . '.' . $logoFile->getClientOriginalExtension();
            $logoFile->move(public_path('uploads/settings'), $logoName);
            $validated['site_logo'] = 'uploads/settings/' . $logoName;
        }

        // Handle favicon upload
        if ($request->hasFile('site_favicon')) {
            if ($settings->site_favicon && file_exists(public_path($settings->site_favicon))) {
                @unlink(public_path($settings->site_favicon));
            }
            if (!file_exists(public_path('uploads/settings'))) {
                mkdir(public_path('uploads/settings'), 0777, true);
            }
            $faviconFile = $request->file('site_favicon');
            $faviconName = uniqid('favicon_') . '.' . $faviconFile->getClientOriginalExtension();
            $faviconFile->move(public_path('uploads/settings'), $faviconName);
            $validated['site_favicon'] = 'uploads/settings/' . $faviconName;
        }

        // Handle support image upload
        if ($request->hasFile('support_image')) {
            if ($settings->support_image && file_exists(public_path($settings->support_image))) {
                @unlink(public_path($settings->support_image));
            }
            if (!file_exists(public_path('uploads/settings'))) {
                mkdir(public_path('uploads/settings'), 0777, true);
            }
            $supportImageFile = $request->file('support_image');
            $supportImageName = uniqid('support_') . '.' . $supportImageFile->getClientOriginalExtension();
            $supportImageFile->move(public_path('uploads/settings'), $supportImageName);
            $validated['support_image'] = 'uploads/settings/' . $supportImageName;
        }

        // Ensure social media URLs have proper protocol
        $socialMediaFields = ['facebook_url', 'x_url', 'youtube_url', 'instagram_url', 'whatsapp_url'];
        foreach ($socialMediaFields as $field) {
            if (!empty($validated[$field]) && !str_starts_with($validated[$field], 'http')) {
                $validated[$field] = 'https://' . $validated[$field];
            }
        }

        $settings->fill($validated);
        $settings->save();

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }

    public function testSmtp(Request $request)
    {
        try {
            $request->validate([
                'test_email' => 'required|email',
                'smtp_host' => 'required|string',
                'smtp_port' => 'required|integer|min:1|max:65535',
                'smtp_username' => 'required|email',
                'smtp_password' => 'required|string',
                'smtp_encryption' => 'nullable|string|in:tls,ssl,',
            ]);

            // Configure SMTP with request data
            config([
                'mail.mailers.smtp.host' => $request->smtp_host,
                'mail.mailers.smtp.port' => $request->smtp_port,
                'mail.mailers.smtp.username' => $request->smtp_username,
                'mail.mailers.smtp.password' => $request->smtp_password,
                'mail.mailers.smtp.encryption' => $request->smtp_encryption ?: null,
                'mail.from.address' => $request->smtp_from_address ?: $request->smtp_username,
                'mail.from.name' => $request->smtp_from_name ?: 'Test Email',
            ]);

            // Send test email
            Mail::raw('This is a test email to verify your SMTP configuration is working correctly.', function($message) use ($request) {
                $message->to($request->test_email)
                       ->subject('SMTP Configuration Test - ' . now()->format('Y-m-d H:i:s'));
            });

            Log::info('SMTP test email sent successfully', [
                'test_email' => $request->test_email,
                'smtp_host' => $request->smtp_host,
                'smtp_port' => $request->smtp_port
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Test email sent successfully! Check your inbox.'
            ]);

        } catch (\Exception $e) {
            Log::error('SMTP test failed', [
                'error' => $e->getMessage(),
                'test_email' => $request->test_email ?? 'not provided',
                'smtp_host' => $request->smtp_host ?? 'not provided'
            ]);

            return response()->json([
                'success' => false, 
                'message' => 'SMTP Test failed: ' . $e->getMessage()
            ]);
        }
    }
}
