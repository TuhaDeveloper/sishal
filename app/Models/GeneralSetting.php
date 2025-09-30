<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $fillable = [
        'site_title',
        'site_description',
        'site_logo',
        'site_favicon',
        'contact_email',
        'contact_phone',
        'contact_address',
        'top_text',
        'footer_text',
        'facebook_url',
        'x_url',
        'youtube_url',
        'instagram_url',
    ];
}
