<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'background_image',
        'background_color',
        'company_name',
        'portal_name',
        'footer_text',
        'quote_text',
        'show_password_toggle',
        'show_quote',
    ];
}