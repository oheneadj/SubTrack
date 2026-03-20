<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'invoice_id', 'renewal_id', 'description', 'period', 'quantity', 'unit_price', 'total',
    ];

    protected $casts = [
        'quantity'   => 'float',
        'unit_price' => 'float',
        'total'      => 'float',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function renewal(): BelongsTo
    {
        return $this->belongsTo(Renewal::class);
    }
}
