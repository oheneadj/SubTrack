<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Receipt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * One-off cleanup for receipts generated before receipts were tied to a
 * specific Payment (see the payment_id migration) — these predate the fix
 * for "two separate payments merge into one receipt" and can no longer be
 * trusted to reflect the current amount_paid/payment history correctly.
 *
 * Only ever deletes an invoice-linked receipt whose payment_id is still
 * null (i.e. it was never touched by the new per-payment flow) — anything
 * generated since the fix is left alone. Dry-run by default; nothing is
 * deleted without --force. Safe to run more than once (idempotent — a
 * second run simply finds nothing left to clean up).
 */
class CleanupLegacyReceipts extends Command
{
    /** @var string */
    protected $signature = 'receipts:cleanup-legacy {--force : Actually delete — without this, only lists what would be deleted}';

    /** @var string */
    protected $description = 'Delete invoice receipts generated before payment_id existed (dry-run unless --force is passed)';

    public function handle(): int
    {
        $legacyReceipts = Receipt::whereNotNull('invoice_id')
            ->whereNull('payment_id')
            ->with('invoice.client')
            ->orderBy('invoice_id')
            ->get();

        if ($legacyReceipts->isEmpty()) {
            $this->info('No legacy receipts found — nothing to clean up.');

            return self::SUCCESS;
        }

        $this->table(
            ['Receipt #', 'Invoice #', 'Client', 'Amount', 'Issued'],
            $legacyReceipts->map(fn (Receipt $r) => [
                $r->receipt_number,
                $r->invoice?->invoice_number ?? '—',
                $r->invoice?->client?->name ?? '—',
                $r->formatted_amount_usd,
                $r->issued_date->format('Y-m-d'),
            ])
        );

        if (! $this->option('force')) {
            $this->warn("Found {$legacyReceipts->count()} legacy receipt(s) above. Re-run with --force to permanently delete them (and their PDF files).");

            return self::SUCCESS;
        }

        if (! $this->confirm("This will PERMANENTLY delete {$legacyReceipts->count()} receipt(s) and their PDF files. This cannot be undone. Continue?")) {
            $this->info('Aborted — nothing was deleted.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($legacyReceipts): void {
            foreach ($legacyReceipts as $receipt) {
                if ($receipt->pdf_path) {
                    Storage::disk('public')->delete($receipt->pdf_path);
                }
                $receipt->delete();
            }
        });

        $this->info("Deleted {$legacyReceipts->count()} legacy receipt(s).");

        return self::SUCCESS;
    }
}
