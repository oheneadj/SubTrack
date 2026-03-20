<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Setting;

class MailTemplate extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'slug',
        'name',
        'subject',
        'body',
        'description',
        'variables',
    ];

    protected $casts = [
        'variables' => 'array',
    ];

    // Scopes
    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    // Helpers
    public static function getBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public function render(array $vars): object
    {
        $subject = $this->subject;
        $body = $this->body;

        // Auto-inject global company variables
        $globalVars = [
            '{company_name}' => Setting::get('business_name', config('app.name')),
            '{company_email}' => Setting::get('business_email', ''),
            '{company_contact_details}' => Setting::get('business_phone', '') . ' ' . Setting::get('business_website', ''),
            '{app_name}' => config('app.name'),
        ];

        $allVars = array_merge($globalVars, $vars);

        foreach ($allVars as $key => $value) {
            $subject = str_replace($key, (string) $value, $subject);
            $body = str_replace($key, (string) $value, $body);
        }

        return (object) [
            'subject' => $subject,
            'body' => $body,
        ];
    }
}
