<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = ['name', 'website', 'support_email'];

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
