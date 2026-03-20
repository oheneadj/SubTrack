<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'client_id', 'project_id', 'invoice_number', 'issued_date',
        'due_date', 'tax_rate', 'tax_amount', 'subtotal', 'total_amount',
        'status', 'pdf_path', 'notes',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'due_date'    => 'date',
        'status'      => InvoiceStatus::class,
        'tax_rate'    => 'float',
        'tax_amount'  => 'float',
        'subtotal'    => 'float',
        'total_amount'=> 'float',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(Renewal::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('amount_usd');
        $this->update([
            'subtotal_usd' => $subtotal,
            'total_usd'    => $subtotal,
        ]);
    }
}
