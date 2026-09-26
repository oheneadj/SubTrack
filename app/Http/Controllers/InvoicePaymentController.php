<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\InitiatePayment;
use App\Exceptions\InvoiceAlreadyPaidException;
use App\Models\Invoice;
use App\Services\Payment\GatewayRegistry;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

/**
 * Handles the POST that initiates a checkout session when a client selects a payment method.
 */
class InvoicePaymentController extends Controller
{
    public function checkout(Invoice $invoice, string $gateway, GatewayRegistry $registry): RedirectResponse
    {
        try {
            $gw = $registry->get($gateway);
        } catch (InvalidArgumentException) {
            return back()->withErrors(['gateway' => 'Unsupported payment method.']);
        }

        try {
            $url = (new InitiatePayment)->execute(
                $invoice,
                $gw,
                route('invoice.pay', ['invoice' => $invoice->ulid]),
            );
        } catch (InvoiceAlreadyPaidException $e) {
            return redirect()->route('invoice.pay', ['invoice' => $invoice->ulid])
                ->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect($url);
    }
}
