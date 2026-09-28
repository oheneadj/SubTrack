<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\InitiatePayment;
use App\Exceptions\InvalidPaymentAmountException;
use App\Exceptions\InvoiceAlreadyPaidException;
use App\Models\Invoice;
use App\Services\Payment\GatewayRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Handles the POST that initiates a checkout session when a client selects a payment method.
 */
class InvoicePaymentController extends Controller
{
    public function checkout(Request $request, Invoice $invoice, string $gateway, GatewayRegistry $registry): RedirectResponse
    {
        try {
            $gw = $registry->get($gateway);
        } catch (InvalidArgumentException) {
            return back()->withErrors(['gateway' => 'Unsupported payment method.']);
        }

        // Optional custom/partial amount from the client, in dollars — defaults
        // to the full remaining balance when not provided.
        $request->validate(['amount' => 'nullable|numeric|min:0.01']);
        $amountCents = $request->filled('amount')
            ? (int) round((float) $request->input('amount') * 100)
            : null;

        try {
            $url = (new InitiatePayment)->execute(
                $invoice,
                $gw,
                route('invoice.pay', ['invoice' => $invoice->ulid]),
                $amountCents,
            );
        } catch (InvoiceAlreadyPaidException|InvalidPaymentAmountException $e) {
            return redirect()->route('invoice.pay', ['invoice' => $invoice->ulid])
                ->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect($url);
    }
}
