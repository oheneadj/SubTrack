<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\ClientMagicLinkMail;
use App\Models\Client;
use App\Models\ClientAuthToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Handles client portal magic link authentication.
 *
 * Flow: client submits email → token generated → magic link emailed → client clicks → session set → portal accessible
 */
class ClientAuthController extends Controller
{
    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        // Always return the same response regardless of whether the email exists —
        // avoids leaking which emails are registered as clients.
        $client = Client::where('email', $request->email)->first();

        if ($client) {
            $token = ClientAuthToken::create([
                'client_id' => $client->id,
                'token' => Str::random(64),
                'expires_at' => now()->addHours(24),
            ]);

            Mail::to($client->email)->send(
                new ClientMagicLinkMail($client, route('client.auth', ['token' => $token->token]))
            );
        }

        return back()->with('success', 'If that email is registered, a login link has been sent.');
    }

    public function authenticate(Request $request, string $token): RedirectResponse
    {
        $record = ClientAuthToken::where('token', $token)->first();

        if (! $record || ! $record->isValid()) {
            return redirect()->route('client.login')
                ->withErrors(['auth' => 'This login link is invalid or has expired. Please request a new one.']);
        }

        $record->update(['used_at' => now()]);
        $request->session()->put('client_id', $record->client_id);
        $request->session()->regenerate();

        return redirect()->route('client.invoices');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('client_id');

        return redirect()->route('client.login')
            ->with('success', 'You have been signed out.');
    }
}
