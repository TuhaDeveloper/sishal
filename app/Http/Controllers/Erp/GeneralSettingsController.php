<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class GeneralSettingsController extends Controller
{
    public function index()
    {
        if(Auth::user()->hasPermissionTo('view settings')){
            $settings = GeneralSetting::first();
            return view('erp.settings.settings',compact('settings'));
        }else{
            return redirect()->route('erp.dashboard');
        }
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
            'facebook_url' => 'nullable|string|max:255',
            'x_url' => 'nullable|string|max:255',
            'youtube_url' => 'nullable|string|max:255',
            'instagram_url' => 'nullable|string|max:255',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'site_favicon' => 'nullable|image|mimes:jpeg,png,ico,webp|max:1024',
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

        $settings->fill($validated);
        $settings->save();

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }
}
