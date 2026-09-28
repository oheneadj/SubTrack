<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentRecordStatus;
use App\Traits\HasPublicUlid;
use App\Traits\LogsActivity;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $ulid
 * @property string $invoice_number
 * @property int $client_id
 * @property int $project_id
 * @property string|null $pdf_path
 * @property string|null $notes
 * @property float $tax_rate
 * @property int $tax_amount
 * @property int $subtotal
 * @property int $total_amount
 * @property int $amount_paid
 * @property string $formatted_subtotal
 * @property string $formatted_tax_amount
 * @property string $formatted_total_amount
 * @property string $formatted_amount_paid
 * @property string $formatted_balance_due
 * @property int $balance_due
 * @property InvoiceStatus $status
 * @property CarbonImmutable $issued_date
 * @property CarbonImmutable $due_date
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Client|null $client
 * @property-read Project|null $project
 * @property-read Collection<int, InvoiceItem> $items
 * @property-read Collection<int, Renewal> $renewals
 * @property-read Collection<int, Payment> $payments
 */
class Invoice extends Model
{
    use HasFactory, HasPublicUlid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'client_id', 'project_id', 'invoice_number', 'issued_date',
        'due_date', 'tax_rate', 'tax_amount', 'subtotal', 'total_amount',
        'amount_paid', 'status', 'pdf_path', 'notes',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'due_date' => 'date',
        'status' => InvoiceStatus::class,
        'tax_rate' => 'float',
        'tax_amount' => 'integer',
        'subtotal' => 'integer',
        'total_amount' => 'integer',
        'amount_paid' => 'integer',
    ];

    /** Frees the unique `invoice_number` value on soft delete so it can be reused by a new invoice. */
    protected static function booted(): void
    {
        static::deleting(function (self $invoice): void {
            $invoice->forceFill(['invoice_number' => "{$invoice->invoice_number}-deleted-{$invoice->id}"])->saveQuietly();
        });

        // "Paid" always means the full amount was received — self-correct
        // amount_paid here so every path that sets status directly (tests,
        // factories, an admin override) can't leave the two out of sync
        // with what recalculatePaymentStatus() would have produced.
        static::saving(function (self $invoice): void {
            if ($invoice->status === InvoiceStatus::Paid && $invoice->amount_paid < $invoice->total_amount) {
                $invoice->amount_paid = $invoice->total_amount;
            }
        });
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return '$'.number_format($this->subtotal / 100, 2);
    }

    public function getFormattedTaxAmountAttribute(): string
    {
        return '$'.number_format($this->tax_amount / 100, 2);
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return '$'.number_format($this->total_amount / 100, 2);
    }

    public function getFormattedAmountPaidAttribute(): string
    {
        return '$'.number_format($this->amount_paid / 100, 2);
    }

    /** Amount still owed, in cents. Never negative even if overpaid. */
    public function getBalanceDueAttribute(): int
    {
        return max(0, $this->total_amount - $this->amount_paid);
    }

    public function getFormattedBalanceDueAttribute(): string
    {
        return '$'.number_format($this->balance_due / 100, 2);
    }

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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    /** True when the invoice has been paid in full. */
    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    /** True when some, but not all, of the invoice total has been paid. */
    public function isPartiallyPaid(): bool
    {
        return $this->status === InvoiceStatus::PartiallyPaid;
    }

    /**
     * Sums every succeeded payment against this invoice and updates
     * amount_paid + status accordingly. Called after any payment is
     * recorded — manual or via a gateway webhook — so both paths share
     * exactly one place that decides Paid vs Partially Paid vs unchanged.
     */
    public function recalculatePaymentStatus(): void
    {
        $amountPaid = (int) $this->payments()
            ->where('status', PaymentRecordStatus::Succeeded)
            ->sum('amount');

        // $amountPaid dropping back to 0 — e.g. voiding the only payment
        // recorded against the invoice — must revert status off Paid/Partially
        // Paid, or the invoice would misrepresent itself as paid with nothing
        // actually received. Draft/Overdue are left as they are; anything
        // else (i.e. Paid/Partially Paid itself) reverts to Sent.
        $status = match (true) {
            $amountPaid >= $this->total_amount && $this->total_amount > 0 => InvoiceStatus::Paid,
            $amountPaid > 0 => InvoiceStatus::PartiallyPaid,
            in_array($this->status, [InvoiceStatus::Draft, InvoiceStatus::Overdue], true) => $this->status,
            default => InvoiceStatus::Sent,
        };

        $this->update([
            'amount_paid' => $amountPaid,
            'status' => $status,
        ]);
    }
}
