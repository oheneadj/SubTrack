<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = ['name', 'email', 'phone', 'company_name'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscriptions(): HasManyThrough
    {
        return $this->hasManyThrough(Subscription::class, Project::class);
    }

    // Scopes
    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%")
                     ->orWhere('email', 'like', "%{$term}%")
                     ->orWhere('company_name', 'like', "%{$term}%");
    }
}
